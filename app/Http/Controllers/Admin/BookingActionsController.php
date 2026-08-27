<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\CancelsBookings;
use App\Concerns\ConfirmsBookings;
use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Booking status transitions (confirm / cancel) for the admin inquiries
 * area. Kept separate from InquiryController's CRUD + payment actions so
 * each controller has a single, focused responsibility.
 */
class BookingActionsController extends Controller
{
    use CancelsBookings;
    use ConfirmsBookings;

    public function __construct(private ActivityLogger $logger)
    {
    }

    public function confirm(Inquiry $inquiry): RedirectResponse
    {
        if ($inquiry->status !== Inquiry::STATUS_PENDING) {
            return back()->with('error', 'Only pending inquiries can be confirmed.');
        }

        try {
            DB::transaction(function () use ($inquiry) {
                $inquiry->update(['status' => Inquiry::STATUS_CONFIRMED]);
                $inquiry->bookBlocks();
                $this->markConfirmed($inquiry);
            });
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        DashboardController::forgetCache();

        $this->logger->record('inquiry.confirmed', $inquiry, "Booking {$inquiry->reference_code} confirmed.");

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} confirmed successfully.");
    }

    public function cancel(Inquiry $inquiry): RedirectResponse
    {
        if ($inquiry->status !== Inquiry::STATUS_PENDING) {
            return back()->with('error', 'Only pending inquiries can be cancelled.');
        }

        // This path only accepts pending inquiries (guarded above), so the
        // stay counter is never decremented here: total_stays is only ever
        // incremented by markConfirmed(), and a pending cancel must not
        // subtract a stay belonging to a different confirmed booking on the
        // same guest.
        $this->cancelBooking($inquiry);
        $this->sendCancellationEmails($inquiry);

        $this->logger->record('inquiry.cancelled', $inquiry, "Booking {$inquiry->reference_code} cancelled.");

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} cancelled successfully.");
    }
}
