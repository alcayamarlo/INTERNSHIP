<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
        return view('admin.users.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Email not found.',
            ])->onlyInput('email');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Wrong password.',
            ])->onlyInput('email');
        }

        if ($user->archived_at) {
            return back()->withErrors([
                'email' => 'This account has been archived. Please contact the administrator.',
            ])->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->onlyInput('email');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->activityLog->log($user, 'login');

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('status', 'Account not activated yet. Enter the 6-digit code sent to your email, or request a new one.');
        }

        return redirect()->route($user->dashboardRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}