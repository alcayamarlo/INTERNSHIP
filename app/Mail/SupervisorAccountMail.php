<?php

namespace App\Mail;

use App\Models\Supervisor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells a new supervisor their account exists and links to the page where they set their password. */
class SupervisorAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    /** $url carries the one-time token; it is only ever sent in this email. */
    public function __construct(public Supervisor $supervisor, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SkillBridge supervisor account at '.($this->supervisor->employer?->company_name ?? 'your company'));
    }

    public function content(): Content
    {
        $days = (int) ceil(config('auth.passwords.'.\App\Services\SupervisorService::BROKER.'.expire', 10080) / 1440);

        return new Content(
            view: 'emails.supervisor-account',
            text: 'emails.supervisor-account-text',
            with: [
                'name' => $this->supervisor->user?->name,
                'company' => $this->supervisor->employer?->company_name,
                'position' => $this->supervisor->position,
                'hrName' => $this->supervisor->createdBy?->name,
                'email' => $this->supervisor->user?->email,
                'url' => $this->url,
                'days' => $days,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
