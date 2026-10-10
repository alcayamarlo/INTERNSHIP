<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Skill Bridge verification code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-code',
            with: ['code' => $this->code],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
