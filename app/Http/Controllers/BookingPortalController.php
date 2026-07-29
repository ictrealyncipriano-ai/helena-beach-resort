<?php

namespace App\Http\Controllers;

use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Mail\BookingCancelled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Guest-facing booking portal: lookup bookings by email + reference code,
 * view booking details, and self-cancel (with 48h cutoff).
 */
class BookingPortalController extends Controller
{
    /** Show email/reference lookup form */
    public function lookupForm()
    {
        return view('pages.booking-lookup');
    }

    /** Find a booking by email + reference code */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'reference_code' => 'required|string',
        ]);

        $inquiry = Inquiry::where('email', $validated['email'])
            ->where('reference_code', $validated['reference_code'])
            ->first();

        if (!$inquiry) {
            return back()->withErrors([
                'reference_code' => 'No booking found with that email and reference code.',
            ])->withInput();
        }

        return redirect()->route('booking.portal.show', $inquiry);
    }

    /** Show booking detail page with cancellation option */
    public function show(Inquiry $inquiry)
    {
        $canCancel = $this->canCancel($inquiry);

        return view('pages.booking-detail', compact('inquiry', 'canCancel'));
    }

    /** Cancel a booking (guest-facing), sends notification to guest and owner */
    public function cancel(Request $request, Inquiry $inquiry)
    {
        if (!$this->canCancel($inquiry)) {
            return back()->with('error', 'This booking cannot be cancelled. Cancellations must be made at least 48 hours before check-in.');
        }

        $inquiry->update(['status' => 'cancelled']);

        if ($inquiry->check_in && $inquiry->check_out && $inquiry->cottage_id) {
            CottageDateBlock::where('cottage_id', $inquiry->cottage_id)
                ->whereBetween('date', [$inquiry->check_in, $inquiry->check_out])
                ->where('reason', "Booked: {$inquiry->reference_code}")
                ->delete();
        }

        if ($inquiry->guest) {
            $inquiry->guest->decrement('total_stays');
        }

        try {
            Mail::to($inquiry->email)->send(new BookingCancelled($inquiry));

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($inquiry));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send cancellation notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('booking.portal.show', $inquiry)
            ->with('success', 'Your booking has been cancelled.');
    }

    /**
     * Check if cancellation is allowed:
     * - Status must be pending or confirmed
     * - Must be at least 48 hours before check-in
     */
    private function canCancel(Inquiry $inquiry): bool
    {
        if (!in_array($inquiry->status, ['pending', 'confirmed'])) {
            return false;
        }

        if ($inquiry->check_in && now()->diffInHours($inquiry->check_in) < 48) {
            return false;
        }

        return true;
    }
}
