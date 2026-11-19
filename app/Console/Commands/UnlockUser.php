<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Services\LoginLockout;
use Illuminate\Console\Command;

class UnlockUser extends Command
{
    protected $signature = 'user:unlock {login : The email address or school ID of the account}';

    protected $description = 'End the lock on an account that had too many wrong passwords';

    public function handle(LoginLockout $lockout): int
    {
        $login = trim((string) $this->argument('login'));
        $user = str_contains($login, '@')
            ? User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first()
            : Student::query()->where('student_id_number', mb_strtoupper($login))->first()?->user;

        if (! $user) {
            $this->error('No account has that email address or school ID.');

            return self::FAILURE;
        }

        $this->info($lockout->unlock($user, null, 'command')
            ? "{$user->name} ({$user->email}) is unlocked and can sign in now."
            : "{$user->name} was not locked. The wrong-password count was reset.");

        return self::SUCCESS;
    }
}
