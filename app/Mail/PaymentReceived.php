<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Receipt sent to the guest when PayMongo confirms a successful payment
 * for their booking.
 */
class PaymentReceived extends Mailable implements ShouldQueue
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
            ->subject("Payment Received — {$this->inquiry->reference_code}")
            ->view('emails.payment-received');
    }
}
