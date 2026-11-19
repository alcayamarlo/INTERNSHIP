<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Two-factor sign-in by email code, with recovery codes for when email is down.
 *
 * - Admins must use it; coordinators, HR and supervisors can switch it on; students never.
 * - It applies once the account is ready (email verified, first password set), so first-login setup is not blocked.
 * - The code step is skipped when APP_ENV=local (config security.two_factor.skip_in_local), so demos are never blocked.
 * - Codes are emailed only to real addresses: never to seeded demo accounts (users.demo_account) or reserved
 *   domains (.test, example.com, ...). Such an account signs in with a recovery code, or after `user:reset-2fa`.
 * - Emailed codes and recovery codes are stored only as keyed hashes (HMAC-SHA256 with APP_KEY).
 */
class TwoFactor
{
    public const TRUST_COOKIE = 'two_factor_trust';

    public function __construct(private ActivityLogService $activityLog) {}

    /* ---------- Who it applies to ---------- */

    public function isRequiredFor(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function canEnable(User $user): bool
    {
        return ! $user->isRole(UserRole::Student) && ! $this->isRequiredFor($user);
    }

    /** Is there a second step at sign-in for this account (ignoring where the app runs)? */
    public function isActiveFor(User $user): bool
    {
        return ($this->isRequiredFor($user) || ($user->two_factor_enabled && ! $user->isRole(UserRole::Student)))
            && $user->hasVerifiedEmail()
            && ! $user->must_change_password;
    }

    /** True on a development machine (APP_ENV=local), where the code step is skipped. */
    public function skippedHere(): bool
    {
        return (bool) config('security.two_factor.skip_in_local') && app()->environment('local');
    }

    /** Will a code be asked for at the next password sign-in of this account? */
    public function challengeNeeded(User $user): bool
    {
        return $this->isActiveFor($user) && ! $this->skippedHere();
    }

    /** Can a code be emailed to this account? Never to demo accounts or addresses that are not real mailboxes. */
    public function canEmail(User $user): bool
    {
        if ($user->demo_account || ! $user->hasVerifiedEmail() || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $domain = mb_strtolower(Str::after((string) $user->email, '@'));
        foreach ((array) config('security.two_factor.reserved_suffixes') as $suffix) {
            if (str_ends_with($domain, $suffix)) {
                return false;
            }
        }

        return ! in_array($domain, (array) config('security.two_factor.reserved_domains'), true);
    }

    /* ---------- The emailed code ---------- */

    /**
     * Makes a new code and emails it. Returns 'sent', 'unavailable' (this account's address gets no codes) or
     * 'failed' (the mail server did not accept it). Either way the person can use a recovery code.
     */
    public function sendCode(User $user): string
    {
        if (! $this->canEmail($user)) {
            return 'unavailable';
        }
        $minutes = (int) config('security.two_factor.code_minutes', 10);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->forceFill([
            'two_factor_code_hash' => $this->hash($code),
            'two_factor_code_expires_at' => now()->addMinutes($minutes),
            'two_factor_attempts' => 0,
        ])->saveQuietly();

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($code, $minutes));
        } catch (\Throwable $exception) {
            report($exception);
            $this->forgetCode($user);

            return 'failed';
        }
        $this->activityLog->log($user, 'two_factor_sent');

        return 'sent';
    }

    /** True when the 6-digit code is right. A right code works once; too many wrong ones cancel it. */
    public function verifyCode(User $user, string $input): bool
    {
        $input = preg_replace('/\D/', '', $input) ?? '';
        $limiterKey = 'two-factor-failures:'.$user->id;
        $max = (int) config('security.two_factor.max_failures_per_hour', 20);
        if (RateLimiter::tooManyAttempts($limiterKey, $max)) {
            return false;
        }

        return DB::transaction(function () use ($user, $input, $limiterKey): bool {
            $row = User::query()->lockForUpdate()->find($user->id);
            $valid = $row && $row->two_factor_code_hash !== null && $row->two_factor_code_expires_at?->isFuture()
                && $row->two_factor_attempts < (int) config('security.two_factor.max_attempts', 5);

            if ($valid && strlen($input) === 6 && hash_equals($row->two_factor_code_hash, $this->hash($input))) {
                $this->forgetCode($row);

                return true;
            }
            RateLimiter::hit($limiterKey, 3600);
            if ($row && $row->two_factor_code_hash !== null) {
                $row->forceFill(['two_factor_attempts' => $row->two_factor_attempts + 1])->saveQuietly();
            }

            return false;
        });
    }

    /** True while this account may not try more codes this hour. */
    public function isThrottled(User $user): bool
    {
        return RateLimiter::tooManyAttempts('two-factor-failures:'.$user->id, (int) config('security.two_factor.max_failures_per_hour', 20));
    }

    public function forgetCode(User $user): void
    {
        $user->forceFill(['two_factor_code_hash' => null, 'two_factor_code_expires_at' => null, 'two_factor_attempts' => 0])->saveQuietly();
    }

    /* ---------- Recovery codes ---------- */

    /**
     * Replaces the recovery codes and returns the new ones in clear text. This is the only time they exist in
     * clear text: only keyed hashes are stored.
     *
     * @return list<string> e.g. ["k7m2p-9x4qa", ...]
     */
    public function generateRecoveryCodes(User $user): array
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789'; // no look-alike characters (i, l, o, 0, 1)
        $codes = [];
        while (count($codes) < (int) config('security.two_factor.recovery_codes', 8)) {
            $code = '';
            foreach (range(1, 10) as $i) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[$code] = substr($code, 0, 5).'-'.substr($code, 5);
        }
        $user->forceFill(['two_factor_recovery_codes' => json_encode(array_map(fn (string $code) => $this->hash($code), array_keys($codes)))])->saveQuietly();
        $this->activityLog->log($user, 'two_factor_codes_generated');

        return array_values($codes);
    }

    public function recoveryCodesLeft(User $user): int
    {
        return count((array) json_decode((string) $user->two_factor_recovery_codes, true));
    }

    /** Uses up a recovery code if it is one of this account's unused ones. */
    public function useRecoveryCode(User $user, string $input): bool
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', mb_strtolower($input)) ?? '';
        $limiterKey = 'two-factor-failures:'.$user->id;
        if (strlen($normalized) !== 10 || RateLimiter::tooManyAttempts($limiterKey, (int) config('security.two_factor.max_failures_per_hour', 20))) {
            RateLimiter::hit($limiterKey, 3600);

            return false;
        }

        $used = DB::transaction(function () use ($user, $normalized): bool {
            $row = User::query()->lockForUpdate()->find($user->id);
            $hashes = (array) json_decode((string) $row?->two_factor_recovery_codes, true);
            $hash = $this->hash($normalized);
            foreach ($hashes as $index => $stored) {
                if (hash_equals((string) $stored, $hash)) {
                    unset($hashes[$index]);
                    $row->forceFill(['two_factor_recovery_codes' => json_encode(array_values($hashes))])->saveQuietly();
                    $user->two_factor_recovery_codes = $row->two_factor_recovery_codes;

                    return true;
                }
            }

            return false;
        });
        if (! $used) {
            RateLimiter::hit($limiterKey, 3600);
        }

        return $used;
    }

    /* ---------- Trusted browsers ---------- */

    public function isTrusted(Request $request, User $user): bool
    {
        $token = (string) $request->cookie(self::TRUST_COOKIE);
        if (strlen($token) < 32) {
            return false;
        }
        $query = DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->where('token_hash', $this->hash($token))->where('expires_at', '>', now());
        if (! $query->exists()) {
            return false;
        }
        $query->update(['last_used_at' => now()]);

        return true;
    }

    /** Remembers this browser for config('security.two_factor.trust_days'); returns the cookie to attach. */
    public function trustBrowser(Request $request, User $user): Cookie
    {
        $token = Str::random(48);
        $days = (int) config('security.two_factor.trust_days', 30);
        DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->where('expires_at', '<', now())->delete();
        DB::table('two_factor_trusted_browsers')->insert([
            'user_id' => $user->id,
            'token_hash' => $this->hash($token),
            'label' => mb_substr(\App\Http\Controllers\AccountDevicesController::describe((string) $request->userAgent()), 0, 120),
            'expires_at' => now()->addDays($days),
            'created_at' => now(),
        ]);

        return cookie(self::TRUST_COOKIE, $token, $days * 1440, '/', null, $request->isSecure(), true, false, 'lax');
    }

    /** Every remembered browser of this account must pass the code step again. */
    public function forgetBrowsers(User $user): int
    {
        return DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->delete();
    }

    /* ---------- Admin tools ---------- */

    /**
     * For an account that cannot get its code (email down, address lost, recovery codes gone): removes the recovery
     * codes, the pending code and every trusted browser, switches optional 2FA off, and lets an admin's next password
     * sign-in through without the code once. They are then sent to make new recovery codes.
     */
    public function reset(User $user): void
    {
        $this->forgetCode($user);
        $this->forgetBrowsers($user);
        RateLimiter::clear('two-factor-failures:'.$user->id);
        $user->forceFill([
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_skip_once' => $this->isRequiredFor($user),
        ])->saveQuietly();
        $this->activityLog->log(null, 'two_factor_reset', ['user_id' => $user->id, 'email' => $user->email, 'via' => 'command']);
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
