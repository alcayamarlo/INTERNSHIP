<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Console\Command;

class ResetTwoFactor extends Command
{
    protected $signature = 'user:reset-2fa {login : The email address or school ID of the account}';

    protected $description = 'Let an account in when its sign-in code cannot be used (email down or lost, recovery codes gone)';

    public function handle(TwoFactor $twoFactor): int
    {
        $login = trim((string) $this->argument('login'));
        $user = str_contains($login, '@')
            ? User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first()
            : Student::query()->where('student_id_number', mb_strtoupper($login))->first()?->user;

        if (! $user) {
            $this->error('No account has that email address or school ID.');

            return self::FAILURE;
        }

        $twoFactor->reset($user);

        $this->info("Two-factor sign-in was reset for {$user->name} ({$user->email}).");
        $this->line($twoFactor->isRequiredFor($user)
            ? '  Their next password sign-in goes through without a code, once, and sends them to make new recovery codes.'
            : '  Two-factor is now off for this account; they can switch it on again on the Two-factor sign-in page.');
        $this->line('  Their recovery codes, pending code and trusted browsers were removed.');

        return self::SUCCESS;
    }
}
