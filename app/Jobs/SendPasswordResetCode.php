<?php

namespace App\Jobs;

use App\Exceptions\MailDeliveryFailed;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Services\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SendPasswordResetCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        $email = mb_strtolower(trim($this->email));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );
        Cache::put($this->attemptKey($user->email), 0, now()->addMinutes(15));

        if (! app(MailSender::class)->send($user->email, new PasswordResetCodeMail($code), 'password_reset')) {
            // The code never reached the user, so it must not stay usable.
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            throw new MailDeliveryFailed('The password reset email could not be sent.');
        }
    }

    private function attemptKey(string $email): string
    {
        return 'password-reset-attempts:'.hash('sha256', mb_strtolower(trim($email)));
    }
}
