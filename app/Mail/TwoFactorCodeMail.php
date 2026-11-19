<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The 6-digit sign-in code (App\Services\TwoFactor). */
class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Skill Bridge sign-in code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.two-factor-code', with: ['code' => $this->code, 'minutes' => $this->minutes]);
    }

    public function attachments(): array
    {
        return [];
    }
}
