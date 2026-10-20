<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single place where the app sends email. A transport failure (for example
 * an invalid Resend key) is logged and reported as false instead of turning
 * the request into a 500.
 */
class MailSender
{
    public function send(string $to, Mailable $mailable, string $context): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Email could not be sent.', [
                'context' => $context,
                'mailer' => config('mail.default'),
                'mailable' => $mailable::class,
                'exception' => $exception::class,
                'error' => self::redact($exception->getMessage()),
            ]);

            return false;
        }
    }

    /**
     * Strips the configured API key from provider error messages before they are logged or printed.
     */
    public static function redact(string $message): string
    {
        $key = (string) config('services.resend.key');

        return $key !== '' ? str_replace($key, '[redacted]', $message) : $message;
    }
}
