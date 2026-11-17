<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\SupervisorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Guest pages: a new supervisor sets their password from the emailed link (7 days, one use), shown as
 * a popup over the landing page like the company invitation. Then they sign in from the landing page.
 */
class SupervisorSetupController extends Controller
{
    public function show(Request $request, string $token)
    {
        $user = $this->userFor((string) $request->query('email'), $token);
        if (! $user) {
            return response()->view('supervisors.setup', ['state' => 'invalid', 'token' => $token, 'email' => null], 410);
        }

        return view('supervisors.setup', ['state' => 'form', 'token' => $token, 'email' => $user->email, 'name' => $user->name]);
    }

    public function store(Request $request, string $token)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        $status = Password::broker(SupervisorService::BROKER)->reset(
            ['email' => $validated['email'], 'password' => $validated['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // hashed by the model cast
                    'remember_token' => Str::random(60),
                    // The emailed link proves the mailbox, so no verification code is needed.
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()->route('supervisor.setup', ['token' => $token, 'email' => $validated['email']]);
        }

        return redirect()->route('home')
            ->with('company_account_ready', 'Your supervisor account is ready. Sign in to continue.')
            ->with('signin_email', $validated['email']);
    }

    /** The supervisor account this link is for, only while the link is still valid. */
    private function userFor(string $email, string $token): ?User
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->where('role', 'supervisor')->first();

        return $user && Password::broker(SupervisorService::BROKER)->tokenExists($user, $token) ? $user : null;
    }
}
