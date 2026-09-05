<?php

namespace App\Concerns;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\BookingCancelled;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Shared cancellation primitives used by the admin and guest booking
 * controllers. Provides the common status-update, block-release, cache
 * invalidation, and email-notification steps that every cancellation path
 * requires.
 */
trait CancelsBookings
{
    /**
     * Cancel an inquiry: set status, release date blocks, and drop the
     * dashboard cache.
     */
    protected function cancelBooking(Inquiry $inquiry): void
    {
        $inquiry->update(['status' => Inquiry::STATUS_CANCELLED]);
        $inquiry->releaseBlocks();
        DashboardController::forgetCache();
    }

    /**
     * Reverse the stay count on the guest profile when a confirmed booking
     * is cancelled. Guards against decrementing a pending booking's count.
     */
    protected function reverseStayIfNeeded(Inquiry $inquiry): void
    {
        if ($inquiry->status === Inquiry::STATUS_CONFIRMED) {
            $inquiry->reverseStay();
        }
    }

    /**
     * Send cancellation emails to the guest and the resort owner.
     */
    protected function sendCancellationEmails(Inquiry $inquiry): void
    {
        try {
            Mail::to($inquiry->email)->queue(new BookingCancelled($inquiry));

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->queue(new BookingCancelled($inquiry));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send cancellation notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
