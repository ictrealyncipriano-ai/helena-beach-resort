<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the guest (and the resort owner) when the guest changes their
 * booking from the booking portal.
 */
class BookingModified extends Mailable
{
    use Queueable, SerializesModels;

    public Inquiry $inquiry;

    /**
     * The booking details as they were before the change, so the email can
     * show what changed instead of only the new schedule.
     *
     * @var array<string, mixed>|null
     */
    public ?array $previous;

    /**
     * @param  array<string, mixed>|null  $previous
     */
    public function __construct(Inquiry $inquiry, ?array $previous = null)
    {
        $this->inquiry = $inquiry;
        $this->previous = $previous;
    }

    public function build(): static
    {
        return $this
            ->subject("Booking Updated — {$this->inquiry->reference_code}")
            ->view('emails.booking-modified');
    }
}