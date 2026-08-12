<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to an admin user when they request a password reset link from the
 * admin login page. Contains a single-use, time-limited reset token.
 */
class AdminPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $resetUrl;

    public function __construct(string $name, string $resetUrl)
    {
        $this->name = $name;
        $this->resetUrl = $resetUrl;
    }

    public function build(): static
    {
        return $this
            ->subject('Reset Your Password — '.config('app.name'))
            ->view('emails.admin-password-reset');
    }
}
