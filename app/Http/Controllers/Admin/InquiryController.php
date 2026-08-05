<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
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

        return view('admin.inquiries.index', compact('inquiries', 'cottages', 'guests'));
    }

    /**
     * Store a walk-in booking taken over the counter or by phone. Tags it
     * with source = walk-in, auto-creates/links a guest profile when no
     * existing guest is selected, and auto-calculates the total from the
     * cottage rate when the amount is left blank.
     */
    public function store(Request $request, InquiryService $inquiryService)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:255',
            'guest_id' => 'nullable|exists:guests,id',
            'booking_type' => 'nullable|in:day_tour,overnight',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'pax' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0',
            'cottage_id' => 'nullable|exists:cottages,id',
            'status' => 'required|in:pending,confirmed,cancelled,expired',
            'message' => 'nullable',
        ]);

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
        ]);

        if (empty($inquiry->guest_id) && $inquiry->email) {
            $guest = Guest::updateOrCreate(
                ['email' => $inquiry->email],
                ['name' => $inquiry->name, 'phone' => $inquiry->phone]
            );
            $inquiry->guest()->associate($guest)->save();
        }

        if ($inquiry->status === 'confirmed') {
            $inquiry->bookBlocks();
            $this->markConfirmed($inquiry);
        } elseif ($inquiry->status === 'pending') {
            $inquiry->reserveBlocks();
        }

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

    public function update(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:255',
            'guest_id' => 'nullable|exists:guests,id',
            'booking_type' => 'nullable|in:day_tour,overnight',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'pax' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0',
            'cottage_id' => 'nullable|exists:cottages,id',
            'status' => 'required|in:pending,confirmed,cancelled,expired',
            'message' => 'nullable',
        ]);

        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
        ];

        $wasConfirmed = $inquiry->status === 'confirmed';

        $inquiry->update($data);
        $inquiry->refresh();

        // Release the blocks held for the original schedule, then re-hold
        // for the new schedule so stale blocks never linger.
        $inquiry->releaseBlocks($original);

        if ($inquiry->status === 'confirmed') {
            $inquiry->bookBlocks();

            if (! $wasConfirmed) {
                $this->markConfirmed($inquiry);
            }
        } elseif ($inquiry->status === 'pending') {
            $inquiry->reserveBlocks();
        }

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->releaseBlocks();
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    public function confirm(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'pending') {
            return back()->with('error', 'Only pending inquiries can be confirmed.');
        }

        $inquiry->update(['status' => 'confirmed']);
        $inquiry->bookBlocks();

        $this->markConfirmed($inquiry);

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

        $inquiry->update(['status' => 'cancelled']);
        $inquiry->releaseBlocks();

        if ($inquiry->guest && $inquiry->guest->total_stays > 0) {
            $inquiry->guest->decrement('total_stays');
        }

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

        if ($inquiry->isRefunded()) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', 'This booking has already been refunded.');
        }

        try {
            $payMongo->refund($inquiry);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', $e->getMessage());
        }

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
