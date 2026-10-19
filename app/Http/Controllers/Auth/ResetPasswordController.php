<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ResetPasswordController extends Controller
{
    public function reset(ResetPasswordRequest $request)
    {
        $validated = $request->validated();
        $email = mb_strtolower(trim($validated['email']));
        $attemptKey = $this->attemptKey($email);

        $result = DB::transaction(function () use ($validated, $email, $attemptKey): array {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            $reset = DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

            if (! $user || ! $reset || Cache::get($attemptKey, 0) >= 5) {
                return ['status' => 'invalid'];
            }

            if (! $reset->created_at || Carbon::parse($reset->created_at)->addMinutes(15)->isPast()) {
                DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
                Cache::forget($attemptKey);

                return ['status' => 'invalid'];
            }

            if (! Hash::check($validated['code'], $reset->token)) {
                Cache::add($attemptKey, 0, now()->addMinutes(15));
                $attempts = Cache::increment($attemptKey);

                if ($attempts >= 5) {
                    DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
                }

                return ['status' => 'invalid'];
            }

            $user->forceFill([
                'password' => $validated['password'],
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
            Cache::forget($attemptKey);

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            return ['status' => 'reset', 'user' => $user];
        });

        if ($result['status'] !== 'reset') {
            $request->session()->put([
                'password_reset_code_requested' => true,
                'password_reset_code_expires_at' => $request->session()->get(
                    'password_reset_code_expires_at',
                    now()->timestamp
                ),
            ]);

            return back()->withErrors([
                'code' => 'The reset code is invalid or expired. Request a new code and try again.',
            ])->withInput($request->safe()->only('email'));
        }

        $request->session()->forget([
            'password_reset_code_requested',
            'password_reset_code_expires_at',
        ]);

        event(new PasswordReset($result['user']));

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }

    private function attemptKey(string $email): string
    {
        return 'password-reset-attempts:'.hash('sha256', $email);
    }
}
