<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the resort owner when a guest cancels a booking that has
 * manually-collected money (cash / bank transfer) which cannot be returned
 * through PayMongo. Flags the amount so the refund is processed offline
 * instead of being silently retained.
 */
class ManualRefundRequired extends Mailable
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
            ->subject("Action Required: Manual Refund — {$this->inquiry->reference_code}")
            ->view('emails.manual-refund-required');
    }
}
