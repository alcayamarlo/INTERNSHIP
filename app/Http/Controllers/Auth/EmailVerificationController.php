<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmailVerificationController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function notice(Request $request)
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->dashboardRoute()));
        }

        return view('auth.verify-email', [
            // Step 1 asks for the email; step 2 asks for the code sent to it.
            'needsEmail' => $user->email === null || ($request->boolean('change') && $user->canSetOwnEmail()),
            'canChangeEmail' => $user->canSetOwnEmail(),
        ]);
    }

    /** A student adds (or corrects) their email; a 6-digit code is sent to it right away. */
    public function storeEmail(Request $request)
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route($user->dashboardRoute());
        }
        abort_unless($user->canSetOwnEmail(), 403);

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address, for example juan.delacruz@gmail.com.',
            'email.unique' => 'This email is already used by another account. Use a different one.',
        ]);

        $user->forceFill(['email' => $validated['email'], 'email_verified_at' => null])->save();
        $this->activityLog->log($user, 'email_added', ['email' => $user->email]);

        if (! $user->sendEmailVerificationNotification()) {
            return redirect()->route('verification.notice')->withErrors([
                'code' => 'Your email was saved, but we could not send the code right now. Press Resend code to try again.',
            ]);
        }

        return redirect()->route('verification.notice')
            ->with('status', "We sent a 6-digit code to {$user->email}. It expires in 10 minutes.");
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/\A[0-9]{6}\z/'],
        ]);

        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->dashboardRoute()));
        }

        if ($user->verification_attempts >= 5) {
            return back()->withErrors([
                'code' => 'Too many incorrect codes. Request a new code to continue.',
            ]);
        }

        if (
            ! $user->verification_code
            || ! $user->verification_code_expires_at
            || $user->verification_code_expires_at->lessThanOrEqualTo(now())
        ) {
            return back()->withErrors([
                'code' => 'Your verification code has expired. Request a new code.',
            ]);
        }

        if (! Hash::check($validated['code'], $user->verification_code)) {
            $user->increment('verification_attempts');

            return back()->withErrors([
                'code' => $user->fresh()->verification_attempts >= 5
                    ? 'Too many incorrect codes. Request a new code to continue.'
                    : 'The verification code is incorrect.',
            ]);
        }

        $user->forceFill([
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'verification_attempts' => 0,
        ])->save();

        $user->markEmailAsVerified();
        $this->activityLog->log($user, 'email_verified', ['email' => $user->email]);

        // First login: the email is confirmed, so the new password comes next.
        if ($user->must_change_password) {
            return redirect()->route('password.force-change')
                ->with('status', 'Your email is verified. Now set a new password.');
        }

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request)
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->dashboardRoute()));
        }

        if ($user->email === null) {
            return redirect()->route('verification.notice');
        }

        if (! $user->sendEmailVerificationNotification()) {
            return back()->withErrors([
                'code' => 'We could not send the verification code right now. Please try again later or contact your administrator.',
            ]);
        }

        return back()->with('status', 'A new 6-digit verification code has been sent to your email address.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
