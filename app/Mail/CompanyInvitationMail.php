<?php

namespace App\Mail;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** $url carries the raw token; it is never stored, only sent in this email. */
    public function __construct(public CompanyInvitation $invitation, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation to partner with St. Cecilia\'s College – Cebu for the OJT Program');
    }

    public function content(): Content
    {
        $coordinator = $this->invitation->invitedBy?->coordinator;

        return new Content(
            view: 'emails.company-invitation',
            text: 'emails.company-invitation-text',
            with: [
                'companyName' => $this->invitation->company_name,
                'coordinatorName' => $this->invitation->invitedBy?->name ?? 'Your OJT coordinator',
                'url' => $this->url,
                'expiresAt' => $this->invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'reference' => $this->invitation->reference,
                // Who to contact: the coordinator who sent the invitation.
                'coordinatorDepartment' => $coordinator?->departmentName(),
                'coordinatorEmail' => $this->invitation->invitedBy?->email,
                'coordinatorPhone' => $this->invitation->invitedBy?->phone,
                'coordinatorOffice' => $coordinator?->office_address,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
