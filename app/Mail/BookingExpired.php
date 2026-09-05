<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the guest when their pending booking request has passed the hold
 * window and has been expired (dates released).
 */
class BookingExpired extends Mailable implements ShouldQueue
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
            ->subject("Booking Request Expired — {$this->inquiry->reference_code}")
            ->view('emails.booking-expired');
    }
}
