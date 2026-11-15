<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $login = trim($credentials['login']);
        $user = str_contains($login, '@')
            ? User::query()->where('email', $login)->first()
            : Student::query()->where('student_id_number', mb_strtoupper($login))->first()?->user;

        if (! $user) {
            // Check a throwaway hash so an unknown account takes as long as a wrong password.
            Hash::check($credentials['password'], self::timingHash());
            $this->activityLog->log(null, 'login_failed', ['login' => mb_substr($login, 0, 120), 'reason' => 'unknown_account']);

            return $this->failed();
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            $this->activityLog->log($user, 'login_failed', ['login' => mb_substr($login, 0, 120), 'reason' => 'wrong_password']);

            return $this->failed();
        }

        $blocked = match (true) {
            (bool) $user->archived_at => ['archived', 'This account has been archived. Please contact the administrator.'],
            ! $user->is_active => ['deactivated', 'Your account has been deactivated. Please contact support.'],
            $user->isSuspended() => ['suspended', $user->suspensionMessage()],
            default => null,
        };
        if ($blocked) {
            $this->activityLog->log($user, 'login_blocked', ['reason' => $blocked[0]]);

            return back()->withErrors(['login' => $blocked[1]])->onlyInput('login');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->activityLog->log($user, 'login');

        // First login: add and verify the email, then set the new password.
        if ($user->must_change_password && $user->hasVerifiedEmail()) {
            return redirect()->route('password.force-change');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('status', $user->email === null
                    ? 'Add your email address to finish setting up your account.'
                    : 'Account not activated yet. Enter the 6-digit code sent to your email, or request a new one.');
        }

        // Only the public "Apply" entry point is resumed after login; any other stored URL is ignored
        // so a user always lands on their own role dashboard.
        $intended = (string) $request->session()->get('url.intended', '');
        if (preg_match('#^/internships/\d+/apply$#', (string) parse_url($intended, PHP_URL_PATH))) {
            return redirect()->intended(route($user->dashboardRoute()));
        }

        return redirect()->route($user->dashboardRoute());
    }

    /**
     * One message for an unknown account and a wrong password, so the form does not reveal which
     * emails or School IDs have accounts. The activity log still records the real reason.
     */
    public const FAILED_MESSAGE = 'Incorrect email/School ID or password.';

    private function failed()
    {
        return back()->withErrors(['login' => self::FAILED_MESSAGE])->onlyInput('login');
    }

    private static function timingHash(): string
    {
        static $hash;

        return $hash ??= Hash::make('skillbridge-login-timing');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $this->activityLog->log($request->user(), 'logout');
        }
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
