<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForcePasswordChangeRequest;
use App\Services\ActivityLogService;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * First-login password change. Accounts start with the birthday as a temporary password
 * (must_change_password = true); EnsurePasswordIsCurrent keeps them on this page until it is changed.
 */
class ForcePasswordChangeController extends Controller
{
    /** Wrong current passwords allowed before the form locks. */
    public const MAX_ATTEMPTS = 5;

    /** How long the form stays locked after too many wrong current passwords. */
    public const LOCKOUT_SECONDS = 900;

    public function __construct(private ActivityLogService $activityLog) {}

    public function edit(Request $request)
    {
        $user = $request->user();

        // The email comes first: add and verify it, then set the new password.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return response()
            ->view('auth.force-password-change', [
                'policy' => PasswordPolicy::forClient($user),
                'lockedSeconds' => RateLimiter::tooManyAttempts(self::lockKey($user->id), self::MAX_ATTEMPTS)
                    ? RateLimiter::availableIn(self::lockKey($user->id))
                    : 0,
            ])
            // The page carries the user's own details for the live checklist, so it is never cached.
            ->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $key = self::lockKey($user->id);

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 1. Locked out: refuse before looking at anything that was typed.
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['current_password' => $this->lockMessage($key)]);
        }

        // 2. Field rules (strength, no personal details, confirmation), plus the two checks that need the stored hash.
        $form = new ForcePasswordChangeRequest;
        $form->setUserResolver(fn () => $user);
        $wrongCurrent = false;
        $validator = Validator::make($request->only('current_password', 'password', 'password_confirmation'), $form->rules(), $form->messages());
        $validator->after(function ($validator) use ($request, $user, &$wrongCurrent) {
            if (! $request->filled('current_password')) {
                return; // "required" already explains it; an empty field is not a guess.
            }
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                $wrongCurrent = true;
                // Kept generic: it never says what the current password is or how close the guess was.
                $validator->errors()->add('current_password', 'Your current password is incorrect.');
            }
        });

        if ($validator->fails()) {
            if ($wrongCurrent) {
                RateLimiter::hit($key, self::LOCKOUT_SECONDS);
                $this->activityLog->log($user, 'password_change_failed', ['reason' => 'wrong_current_password', 'attempts' => RateLimiter::attempts($key)]);

                // 3. Too many wrong guesses: sign the session out and lock the form for 15 minutes.
                if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                    $this->activityLog->log($user, 'password_change_locked', ['minutes' => self::LOCKOUT_SECONDS / 60]);
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors(['login' => 'For your security you were signed out after too many incorrect attempts. Try again in 15 minutes.']);
                }
            }

            // Passwords are never flashed back into the form.
            return back()->withErrors($validator);
        }

        // 4. Save the new password (bcrypt hash) and drop the "must change" flag and old remember-me token.
        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();
        RateLimiter::clear($key);

        // 5. End every other session for this account, then give this browser a fresh session and CSRF token.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '<>', $request->session()->getId())
                ->delete();
        }
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        // 6. Audit log: who and when (the log adds the time and IP). Never the password.
        $this->activityLog->log($user, 'password_force_change');

        return redirect()->route($user->dashboardRoute())->with('success', 'Your password has been updated.');
    }

    public static function lockKey(int $userId): string
    {
        return 'password-force-change:'.$userId;
    }

    private function lockMessage(string $key): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

        return "Too many incorrect attempts. Try again in {$minutes} ".Str::plural('minute', $minutes).'.';
    }
}
