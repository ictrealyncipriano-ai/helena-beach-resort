<?php

namespace App\Concerns;

use App\Mail\BookingConfirmed;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Shared confirmation helper: records the stay on the guest profile and
 * emails the guest. Used by the admin confirm action and by the walk-in
 * store path when a booking is created directly as confirmed, so every
 * confirmation path behaves identically.
 */
trait ConfirmsBookings
{
    /**
     * Record the stay on the guest profile and email the guest to confirm
     * their booking.
     */
    protected function markConfirmed(Inquiry $inquiry): void
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
}
