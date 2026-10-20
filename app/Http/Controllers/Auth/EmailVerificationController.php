<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->dashboardRoute()));
        }

        return view('auth.verify-email');
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

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request)
    {
        $user = $this->user($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($user->dashboardRoute()));
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
