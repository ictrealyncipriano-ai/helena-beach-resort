<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $query = Guest::withCount('inquiries')->with('inquiries.cottage');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $guests = $query->latest()->paginate(15);

        $guestsData = $guests->map(function ($guest) {
            $inquiries = $guest->inquiries;
            $paid = $inquiries->filter(fn ($i) => $i->isPaid());
            $failed = $inquiries->filter(fn ($i) => $i->hasFailedPayment());
            $refunded = $inquiries->filter(fn ($i) => $i->isRefunded());

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
                    'paid_count' => $paid->count(),
                    'paid_amount' => $paid->sum('paid_amount'),
                    'failed_count' => $failed->count(),
                    'refunded_count' => $refunded->count(),
                ],
                'inquiries' => $inquiries->map(fn ($i) => [
                    'reference_code' => $i->reference_code,
                    'cottage_name' => $i->cottage?->name ?? 'N/A',
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
                    'total_amount' => $i->total_amount !== null ? '₱ ' . number_format($i->total_amount, 2) : '—',
                ])->values(),
            ];
        })->values();

        return view('admin.guests.index', compact('guests', 'guestsData'));
    }

    public function show(Guest $guest)
    {
        $guest->load('inquiries.cottage');

        $inquiries = $guest->inquiries;
        $paidCount = $inquiries->filter(fn ($i) => $i->isPaid())->count();
        $paidAmount = $inquiries->filter(fn ($i) => $i->isPaid())->sum('paid_amount');
        $failedCount = $inquiries->filter(fn ($i) => $i->hasFailedPayment())->count();
        $refundedCount = $inquiries->filter(fn ($i) => $i->isRefunded())->count();

        return view('admin.guests.show', compact(
            'guest',
            'paidCount',
            'paidAmount',
            'failedCount',
            'refundedCount',
        ));
    }

    public function edit(Guest $guest)
    {
        return view('admin.guests.form', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:guests,email,' . $guest->id,
            'phone' => 'nullable|max:20',
            'notes' => 'nullable',
        ]);

        $guest->update($data);

        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest)
    {
        $guest->delete();
        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest deleted successfully.');
    }
}
