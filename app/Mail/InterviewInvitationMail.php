<?php

namespace App\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $icsContent;

    public function __construct(
        public string $internshipTitle,
        public DateTimeInterface $interviewAt,
        public string $companyName = '',
        public string $location = '',
        public string $recipientName = ''
    ) {
        $this->subject = 'Interview invitation for '.$this->internshipTitle;
        $this->icsContent = $this->buildIcs();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview-invitation',
            with: [
                'internshipTitle' => $this->internshipTitle,
                'companyName' => $this->companyName,
                'location' => $this->location,
                'interviewAt' => $this->interviewAt,
                'recipientName' => $this->recipientName,
            ],
        );
    }

    public function build(): self
    {
        return $this->subject($this->subject)
            ->view('emails.interview-invitation', [
                'internshipTitle' => $this->internshipTitle,
                'companyName' => $this->companyName,
                'location' => $this->location,
                'interviewAt' => $this->interviewAt,
                'recipientName' => $this->recipientName,
            ])
            ->attachData($this->icsContent, 'interview-invitation.ics', [
                'mime' => 'text/calendar; charset=UTF-8; method=REQUEST',
            ]);
    }

    private function buildIcs(): string
    {
        $start = $this->interviewAt->format('Ymd\THis');
        $end = $this->interviewAt->modify('+1 hour')->format('Ymd\THis');
        $uid = 'interview-'.time().'-'.bin2hex(random_bytes(4)).'@internship.local';
        $summary = 'Interview for '.$this->internshipTitle;
        $location = $this->location ?: 'Virtual Meeting';

        return "BEGIN:VCALENDAR\r\n".
            "VERSION:2.0\r\n".
            "PRODID:-//INTERNSHIP//Interview Invitation//EN\r\n".
            "BEGIN:VEVENT\r\n".
            "UID:{$uid}\r\n".
            "DTSTAMP:{$this->interviewAt->format('Ymd\THis')}\r\n".
            "DTSTART:{$start}\r\n".
            "DTEND:{$end}\r\n".
            "SUMMARY:{$summary}\r\n".
            "LOCATION:{$location}\r\n".
            "DESCRIPTION:Interview for {$this->internshipTitle}.\r\n".
            "END:VEVENT\r\n".
            "END:VCALENDAR\r\n";
    }
}
