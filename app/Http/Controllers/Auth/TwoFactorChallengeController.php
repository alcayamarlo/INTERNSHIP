<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** The second step of sign-in: the emailed 6-digit code, or a recovery code when email is not available. */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'two_factor.pending';

    /** How long after the password the code may be entered. */
    private const PENDING_MINUTES = 15;

    public function __construct(private TwoFactor $twoFactor, private ActivityLogService $activityLog) {}

    public function show(Request $request)
    {
        if (! $user = $this->pendingUser($request)) {
            return $this->expired();
        }

        return view('auth.two-factor', [
            'email' => self::mask((string) $user->email),
            'state' => session('two_factor_sent', $this->twoFactor->canEmail($user) ? 'sent' : 'unavailable'),
            'hasRecoveryCodes' => $this->twoFactor->recoveryCodesLeft($user) > 0,
            'trustDays' => (int) config('security.two_factor.trust_days', 30),
        ]);
    }

    public function verify(Request $request, LoginController $login)
    {
        if (! $user = $this->pendingUser($request)) {
            return $this->expired();
        }
        $request->validate(['code' => ['required', 'string', 'max:30']], ['code.required' => 'Enter your sign-in code or a recovery code.']);

        if ($this->twoFactor->isThrottled($user)) {
            $this->activityLog->log($user, 'two_factor_failed', ['reason' => 'throttled']);

            return back()->withErrors(['code' => 'Too many wrong codes. Try again in an hour, or ask an administrator.']);
        }

        $input = trim((string) $request->input('code'));
        $isEmailCode = (bool) preg_match('/^\d{3}\s?\d{3}$/', $input);
        $passed = $isEmailCode ? $this->twoFactor->verifyCode($user, $input) : $this->twoFactor->useRecoveryCode($user, $input);

        if (! $passed) {
            $this->activityLog->log($user, 'two_factor_failed', ['reason' => $isEmailCode ? 'wrong_code' : 'wrong_recovery_code']);

            return back()->withErrors(['code' => $isEmailCode
                ? 'That code is wrong or has expired. Check the email, or request a new code.'
                : 'That is not an unused recovery code.']);
        }

        $request->session()->forget(self::SESSION_KEY);
        $left = $this->twoFactor->recoveryCodesLeft($user);
        $landing = null;
        if (! $isEmailCode) {
            $this->activityLog->log($user, 'two_factor_recovery_used', ['left' => $left]);
            $landing = ['account.security', $left === 0
                ? 'That was your last recovery code. Make new ones now.'
                : "Recovery code accepted. You have {$left} left."];
        } else {
            $this->activityLog->log($user, 'two_factor_passed');
            if ($left === 0 && $this->twoFactor->isRequiredFor($user)) {
                $landing = ['account.security', 'Save your recovery codes now. You need one to sign in if email is down.'];
            }
        }

        $response = $login->completeSignIn($request, $user, $landing);
        if ($request->boolean('trust')) {
            $response->headers->setCookie($this->twoFactor->trustBrowser($request, $user));
        }

        return $response;
    }

    public function resend(Request $request)
    {
        if (! $user = $this->pendingUser($request)) {
            return $this->expired();
        }

        return redirect()->route('two-factor.challenge')->with('two_factor_sent', $this->twoFactor->sendCode($user));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = (array) $request->session()->get(self::SESSION_KEY);
        if (empty($pending['user_id']) || now()->getTimestamp() - (int) ($pending['at'] ?? 0) > self::PENDING_MINUTES * 60) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return User::query()->find($pending['user_id']);
    }

    private function expired()
    {
        return redirect()->route('login')->withErrors(['login' => 'Your sign-in expired. Please sign in again.']);
    }

    /** "ab****@gmail.com": enough to recognise the mailbox, not enough to learn it. */
    public static function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return $domain === '' ? '' : Str::substr($name, 0, 2).str_repeat('*', max(2, mb_strlen($name) - 2)).'@'.$domain;
    }
}
