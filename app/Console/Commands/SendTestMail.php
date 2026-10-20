<?php

namespace App\Console\Commands;

use App\Services\MailSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {email : Address that should receive the test email}';

    protected $description = 'Send one test email with the configured mailer and print the result';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::INVALID;
        }

        $mailer = (string) config('mail.default');
        $from = config('mail.from.address');

        // Only report whether the key exists; never print its value.
        $this->line("Mailer: {$mailer}");
        $this->line('From: '.($from ?: '(MAIL_FROM_ADDRESS is not set)'));
        if ($mailer === 'resend') {
            $this->line('RESEND_API_KEY: '.(filled(config('services.resend.key')) ? 'set' : 'MISSING'));
        }
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\", so nothing is actually delivered.");
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you received it, email delivery works.',
                fn ($message) => $message->to($email)->subject(config('app.name').' test email')
            );
        } catch (\Throwable $exception) {
            $this->error('Send failed ('.$exception::class.'): '.MailSender::redact($exception->getMessage()));

            return self::FAILURE;
        }

        $this->info("Test email sent to {$email}.");

        return self::SUCCESS;
    }
}
