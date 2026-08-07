<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InquiryRequest;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Mail\RefundReceived;
use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\PayMongoService;
use App\Services\InquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    /**
     * Drop the cached admin dashboard aggregates so the next dashboard view
     * reflects the mutation. Called by every action that changes the counts,
     * revenue, or booking-type distribution shown on the dashboard.
     */
    private function forgetDashboardCache(): void
    {
        Cache::forget(DashboardController::cacheKey());
    }

    public function index(Request $request)
    {
        $query = Inquiry::with(['cottage', 'guest']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('cottage_id')) {
            $query->where('cottage_id', $request->cottage_id);
        }

        $inquiries = $query->latest()->paginate(15);
        $cottages = Cottage::pluck('name', 'id');
        $guests = Guest::pluck('name', 'id');
        $cottageRates = Cottage::select('id', 'name', 'rate_daytour', 'rate_overnight')
            ->get()
            ->mapWithKeys(fn ($c) => [
            $c->id => [
                'name' => $c->name,
                'day_tour' => (float) $c->rate_daytour,
                'overnight' => (float) $c->rate_overnight,
            ],
        ]);

        return view('admin.inquiries.index', compact('inquiries', 'cottages', 'guests', 'cottageRates'));
    }

    /**
     * Store a walk-in booking taken over the counter or by phone. Tags it
     * with source = walk-in, auto-creates/links a guest profile when no
     * existing guest is selected, and auto-calculates the total from the
     * cottage rate when the amount is left blank.
     */
    public function store(InquiryRequest $request, InquiryService $inquiryService)
    {
        $data = $request->validated();

        $totalAmount = $data['total_amount'] ?? null;
        if ($totalAmount === null || $totalAmount === '') {
            $totalAmount = $inquiryService->calculateTotal($data);
        }

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'pax' => $data['pax'] ?? null,
            'cottage_id' => $data['cottage_id'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'message' => $data['message'] ?? null,
            'booking_type' => $data['booking_type'] ?? null,
            'total_amount' => $totalAmount,
            'status' => $data['status'],
            'source' => 'walk-in',
            'reference_code' => Inquiry::generateReferenceCode(),
        ]);

        if (empty($inquiry->guest_id) && $inquiry->email) {
            // Include soft-deleted rows in the lookup: SoftDeletes hides
            // trashed guests from updateOrCreate, whose INSERT would then
            // collide with the unique guests.email index (HTTP 500). Reuse and
            // restore the trashed profile instead. Admin-entered name/phone
            // are trusted here, so they are refreshed on the record.
            $guest = Guest::withTrashed()->firstOrCreate(
                ['email' => $inquiry->email],
                ['name' => $inquiry->name, 'phone' => $inquiry->phone]
            );

            if ($guest->trashed()) {
                $guest->restore();
                // A trashed profile carries another person's history; reset
                // the transient stay/notes fields so the 'new' guest does
                // not inherit it.
                $guest->update(['total_stays' => 0, 'last_stay_at' => null, 'notes' => null]);
            }

            $guest->update(['name' => $inquiry->name, 'phone' => $inquiry->phone]);
            $inquiry->guest()->associate($guest)->save();
        }

        try {
            DB::transaction(function () use ($inquiry) {
                if ($inquiry->status === 'confirmed') {
                    $inquiry->bookBlocks();
                    $this->markConfirmed($inquiry);
                } elseif ($inquiry->status === 'pending') {
                    $inquiry->reserveBlocks();
                }
            });
        } catch (BookingConflictException $e) {
            // The inquiry was never usable (its blocks rolled back), so it
            // should not linger as a trashed row — fully remove it.
            $inquiry->forceDelete();

            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->forgetDashboardCache();

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Walk-in inquiry {$inquiry->reference_code} created successfully.");
    }

    public function show(Inquiry $inquiry)
    {
        $inquiry->load(['cottage', 'guest']);

        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function edit(Inquiry $inquiry)
    {
        $inquiry->load(['cottage', 'guest']);
        $cottages = Cottage::pluck('name', 'id');
        $guests = Guest::pluck('name', 'id');

        return view('admin.inquiries.form', compact('inquiry', 'cottages', 'guests'));
    }

    public function update(InquiryRequest $request, Inquiry $inquiry)
    {
        $data = $request->validated();

        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
        ];

        $wasConfirmed = $inquiry->status === 'confirmed';

        // Release the blocks held for the original schedule, then re-hold
        // for the new schedule so stale blocks never linger. All changes are
        // transactional: if the new dates are taken, nothing is changed and
        // the original hold is preserved.
        try {
            DB::transaction(function () use ($inquiry, $data, $original, $wasConfirmed) {
                $inquiry->update($data);
                $inquiry->refresh();

                $inquiry->releaseBlocks($original);

                if ($inquiry->status === 'confirmed') {
                    $inquiry->bookBlocks();

                    if (! $wasConfirmed) {
                        $this->markConfirmed($inquiry);
                    }
                } elseif ($inquiry->status === 'pending') {
                    $inquiry->reserveBlocks();
                }

                // A previously-confirmed booking moved off confirmed
                // (cancelled/expired): reverse the stay that markConfirmed()
                // recorded so the guest's count never drifts upward.
                if ($wasConfirmed && $inquiry->status !== 'confirmed'
                    && $inquiry->guest && $inquiry->guest->total_stays > 0) {
                    $inquiry->guest->decrement('total_stays');
                }
            });
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->forgetDashboardCache();

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->releaseBlocks();
        $inquiry->delete();
        $this->forgetDashboardCache();

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    public function confirm(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'pending') {
            return back()->with('error', 'Only pending inquiries can be confirmed.');
        }

        try {
            DB::transaction(function () use ($inquiry) {
                $inquiry->update(['status' => 'confirmed']);
                $inquiry->bookBlocks();
                $this->markConfirmed($inquiry);
            });
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->forgetDashboardCache();

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} confirmed successfully.");
    }

    /**
     * Record the stay on the guest profile and email the guest to confirm
     * their booking. Shared by the Confirm button and the edit form so
     * both confirmation paths behave identically.
     */
    private function markConfirmed(Inquiry $inquiry): void
    {
        if ($inquiry->guest) {
            $inquiry->guest->increment('total_stays');
            $inquiry->guest->update(['last_stay_at' => now()]);
        }

        try {
            Mail::to($inquiry->email)->send(new BookingConfirmed($inquiry));
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation email', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function cancel(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'pending') {
            return back()->with('error', 'Only pending inquiries can be cancelled.');
        }

        $wasConfirmed = $inquiry->status === 'confirmed';

        $inquiry->update(['status' => 'cancelled']);
        $inquiry->releaseBlocks();

        // total_stays is only incremented by markConfirmed(), and this path
        // only accepts pending inquiries (guarded above), so a pending cancel
        // must never decrement the counter — doing so silently subtracts a
        // stay belonging to a different confirmed booking on the same guest.
        if ($wasConfirmed && $inquiry->guest && $inquiry->guest->total_stays > 0) {
            $inquiry->guest->decrement('total_stays');
        }

        $this->forgetDashboardCache();

        try {
            Mail::to($inquiry->email)->send(new BookingCancelled($inquiry));

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($inquiry));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send booking cancelled email', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} cancelled successfully.");
    }

    /**
     * Mark a confirmed booking as paid (e.g. manual override for bank
     * transfer or cash-on-site settlements).
     */
    public function markPaid(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed bookings can be marked as paid.');
        }

        if ($inquiry->isPaid()) {
            return back()->with('error', 'This booking has already been paid.');
        }

        $inquiry->update([
            'paid_at' => now(),
            'paid_amount' => $inquiry->total_amount,
            'payment_method' => 'manual',
        ]);

        $this->forgetDashboardCache();

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', "Booking {$inquiry->reference_code} marked as paid.");
    }

    /**
     * Refund a paid booking via PayMongo and cancel it. Guards against
     * refunding unpaid or already-refunded bookings.
     */
    public function refund(Inquiry $inquiry, PayMongoService $payMongo)
    {
        if (! $inquiry->isPaid()) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', 'This booking has no payment to refund.');
        }

        // Atomically claim the refund BEFORE calling PayMongo so two
        // concurrent requests can never double-refund (TOCTOU guard).
        $claimed = Inquiry::where('id', $inquiry->id)
            ->whereNotNull('paid_at')
            ->whereNull('refunded_at')
            ->update(['refunded_at' => now()]);

        if ($claimed !== 1) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', 'This booking has already been refunded.');
        }

        try {
            $payMongo->refund($inquiry);
        } catch (\RuntimeException $e) {
            // Roll the claim back so the admin can retry after fixing the cause.
            // A model-level update() would skip the column: the in-memory
            // refunded_at is still null (the claim was a bulk update), so it
            // never registers as dirty. Update at the query level instead.
            Inquiry::where('id', $inquiry->id)->update(['refunded_at' => null]);

            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', $e->getMessage());
        }

        $inquiry->refresh();
        $wasConfirmed = $inquiry->status === 'confirmed';

        $inquiry->update([
            'status' => 'cancelled',
            'refunded_at' => now(),
            'refund_amount' => $inquiry->paid_amount ?? $inquiry->total_amount,
        ]);
        $inquiry->releaseBlocks();

        if ($wasConfirmed && $inquiry->guest && $inquiry->guest->total_stays > 0) {
            $inquiry->guest->decrement('total_stays');
        }

        $this->forgetDashboardCache();

        try {
            Mail::to($inquiry->email)->send(new RefundReceived($inquiry));
        } catch (\Exception $e) {
            Log::error('Failed to send refund email', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', "Payment for {$inquiry->reference_code} refunded and booking cancelled.");
    }
}
