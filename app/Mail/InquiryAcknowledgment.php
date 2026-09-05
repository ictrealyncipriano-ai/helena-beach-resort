<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Acknowledgment sent to the guest as soon as their booking/inquiry is
 * submitted. Contains their reference code and submitted details so they
 * can keep track of the request before the admin confirms it.
 */
class InquiryAcknowledgment extends Mailable implements ShouldQueue
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
            ->subject("Booking Received — Your Reference: {$this->inquiry->reference_code}")
            ->view('emails.inquiry-acknowledgment');
    }
}
