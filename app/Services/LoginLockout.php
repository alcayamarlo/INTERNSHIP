<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Locks an account after config('security.lockout.attempts') wrong passwords within the window, whatever address
 * they come from (the per-address rate limit on the sign-in route cannot stop guessing spread over many addresses).
 * The lock ends by itself after lock_minutes; an admin can end it sooner (Users page, `php artisan user:unlock`).
 */
class LoginLockout
{
    public function __construct(private ActivityLogService $activityLog, private NotificationService $notifications) {}

    /** The end of the current lock, or null when the account can sign in. */
    public function lockedUntil(User $user): ?Carbon
    {
        return $user->locked_until !== null && $user->locked_until->isFuture() ? $user->locked_until : null;
    }

    /** Counts one wrong password. Returns the end of the lock when this attempt locked the account. */
    public function recordFailure(User $user): ?Carbon
    {
        $settings = (array) config('security.lockout');
        $lockedUntil = DB::transaction(function () use ($user, $settings): ?Carbon {
            $row = User::query()->lockForUpdate()->find($user->id);
            if (! $row) {
                return null;
            }
            $windowStart = now()->subMinutes((int) ($settings['window_minutes'] ?? 15));
            $count = $row->last_failed_login_at !== null && $row->last_failed_login_at->greaterThan($windowStart)
                ? $row->failed_logins + 1
                : 1;
            $until = $count >= (int) ($settings['attempts'] ?? 10) ? now()->addMinutes((int) ($settings['lock_minutes'] ?? 15)) : null;

            $row->forceFill([
                'failed_logins' => $until ? 0 : $count,
                'last_failed_login_at' => now(),
                'locked_until' => $until ?? $row->locked_until,
            ])->saveQuietly();
            $user->setRawAttributes($row->getAttributes(), true);

            return $until;
        });

        if ($lockedUntil) {
            $this->activityLog->log($user, 'account_locked', ['until' => $lockedUntil->toIso8601String(), 'attempts' => (int) ($settings['attempts'] ?? 10)]);
            $this->notifications->send(
                $user,
                'account_security',
                'Your account was locked',
                'There were '.($settings['attempts'] ?? 10).' wrong password attempts on your account, so it is locked until '
                    .$lockedUntil->timezone('Asia/Manila')->format('g:i A').'. If this was not you, change your password after the lock ends, or ask the administrator.',
            );
        }

        return $lockedUntil;
    }

    /** After a successful sign-in the count starts again. */
    public function clear(User $user): void
    {
        if ($user->failed_logins > 0 || $user->locked_until !== null) {
            $user->forceFill(['failed_logins' => 0, 'last_failed_login_at' => null, 'locked_until' => null])->saveQuietly();
        }
    }

    /** Ends a lock now. $by is the admin, or null for the artisan command. Returns false when nothing was locked. */
    public function unlock(User $user, ?User $by = null, string $how = 'admin'): bool
    {
        $wasLocked = $this->lockedUntil($user) !== null;
        $user->forceFill(['failed_logins' => 0, 'last_failed_login_at' => null, 'locked_until' => null])->saveQuietly();
        if ($wasLocked) {
            $this->activityLog->log($by, 'account_unlocked', ['user_id' => $user->id, 'email' => $user->email, 'via' => $how]);
        }

        return $wasLocked;
    }

    public static function message(Carbon $until): string
    {
        return 'Too many wrong passwords. This account is locked until '.$until->timezone('Asia/Manila')->format('g:i A')
            .'. Try again then, or ask the administrator to unlock it.';
    }
}
