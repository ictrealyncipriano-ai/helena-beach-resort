<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\Guest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(Request $request): View
    {
        // Aggregate stats in SQL instead of hydrating every inquiry and its
        // cottage model for the page. The inquiry rows are still loaded (lean,
        // no cottage relation) for the booking-history modal, and cottage
        // names come from a single lightweight pluck.
        $query = Guest::withCount('inquiries')
            ->withCount(['inquiries as paid_count' => fn ($q) => $q->where('amount_paid', '>', 0)])
            ->withCount(['inquiries as failed_count' => fn ($q) => $q->whereNotNull('payment_failed_at')])
            ->withCount(['inquiries as refunded_count' => fn ($q) => $q->whereNotNull('refunded_at')])
            ->withSum(['inquiries as paid_amount' => fn ($q) => $q->where('amount_paid', '>', 0)], 'amount_paid')
            // Hydrate recent inquiries per guest for the history modal (counts
            // come from withCount/withSum above). No global limit here: a LIMIT
            // inside eager loads applies to the whole query, not per parent.
            ->with(['inquiries' => fn ($q) => $q->latest()->select(
                'id', 'guest_id', 'reference_code', 'cottage_id', 'check_in',
                'check_out', 'booking_type', 'status', 'amount_paid',
                'payment_failed_at', 'refunded_at', 'total_amount', 'payment_method',
            )]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $guests = $query->latest()->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        $cottageNames = Cottage::pluck('name', 'id');

        $guestsData = $guests->map(function ($guest) use ($cottageNames) {
            $inquiries = $guest->inquiries;

            return [
                'id' => $guest->id,
                'name' => $guest->name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'notes' => $guest->notes,
                'total_stays' => $guest->total_stays,
                'last_stay' => $guest->last_stay_at?->format('M d, Y'),
                'created' => $guest->created_at->format('M d, Y'),
                'inquiries_count' => $guest->inquiries_count,
                'stats' => [
                    'paid_count' => (int) $guest->paid_count,
                    'paid_amount' => $guest->paid_amount ?? 0,
                    'failed_count' => (int) $guest->failed_count,
                    'refunded_count' => (int) $guest->refunded_count,
                ],
                'inquiries' => $inquiries->map(fn ($i) => [
                    'reference_code' => $i->reference_code,
                    'cottage_name' => $cottageNames[$i->cottage_id] ?? 'N/A',
                    'check_in' => $i->check_in?->format('M d, Y') ?? '—',
                    'check_out' => $i->check_out?->format('M d, Y') ?? '—',
                    'booking_type' => $i->booking_type,
                    'booking_type_label' => $i->booking_type ? ucfirst(str_replace('_', ' ', $i->booking_type)) : 'Inquiry',
                    'status' => $i->status,
                    'payment_key' => $i->isRefunded() ? 'refunded'
                        : ($i->isPaid() ? 'paid'
                        : ($i->hasFailedPayment() ? 'failed' : 'unpaid')),
                    'payment_label' => $i->isRefunded() ? 'Refunded'
                        : ($i->isPaid() ? 'Paid'
                        : ($i->hasFailedPayment() ? 'Payment Failed' : 'Unpaid')),
                    'payment_method' => $i->paymentMethodLabel(),
                    'total_amount' => $i->total_amount !== null ? formatPrice($i->total_amount) : '—',
                ])->values(),
            ];
        })->values();

        return view('admin.guests.index', compact('guests', 'guestsData'));
    }

    public function show(Guest $guest): View
    {
        $guest->loadCount('inquiries');
        $guest->loadCount(['inquiries as paid_count' => fn ($q) => $q->where('amount_paid', '>', 0)]);
        $guest->loadCount(['inquiries as failed_count' => fn ($q) => $q->whereNotNull('payment_failed_at')]);
        $guest->loadCount(['inquiries as refunded_count' => fn ($q) => $q->whereNotNull('refunded_at')]);
        $guest->loadSum(['inquiries as paid_amount' => fn ($q) => $q->where('amount_paid', '>', 0)], 'amount_paid');
        $guest->load(['inquiries.cottage:id,name']);

        return view('admin.guests.show', [
            'guest' => $guest,
            'paidCount' => (int) $guest->paid_count,
            'paidAmount' => $guest->paid_amount ?? 0,
            'failedCount' => (int) $guest->failed_count,
            'refundedCount' => (int) $guest->refunded_count,
        ]);
    }

    public function edit(Guest $guest): View
    {
        return view('admin.guests.form', compact('guest'));
    }

    public function update(Request $request, Guest $guest, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:guests,email,'.$guest->id,
            'phone' => 'nullable|max:20',
            'notes' => 'nullable',
        ]);

        $guest->update($data);

        $logger->record('guest.updated', $guest, "Guest {$guest->name} updated.", [
            'email' => $guest->email,
        ]);

        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest, ActivityLogger $logger): RedirectResponse
    {
        $guest->delete();

        $logger->record('guest.deleted', $guest, "Guest {$guest->name} deleted.", [
            'email' => $guest->email,
        ]);

        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest deleted successfully.');
    }
}
