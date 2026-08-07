<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once (guarded by the inquiry's expiry_warned_at flag) when a pending
 * booking request is within ~12h of the hold window expiring, so the guest
 * can still act before their dates are released.
 */
class BookingExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    public Inquiry $inquiry;

    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    public function build(): static
    {
        return $this
            ->subject("Don't Forget — Your Booking Request Expires Soon ({$this->inquiry->reference_code})")
            ->view('emails.booking-expiring-soon');
    }
}
