<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Jobs\SendPasswordResetCode;
use Illuminate\Support\Facades\Bus;

class ForgotPasswordController extends Controller
{
    /**
     * Display the forgot password form.
     */
    public function showResetForm()
    {
       return view('admin.users.auth.forgot-password');
    }

    /**
     * Queue the same reset-code job for known and unknown email addresses.
     */
    public function sendResetCode(ForgotPasswordRequest $request)
    {
        Bus::dispatch(new SendPasswordResetCode($request->validated('email')));

        return back()->with('status', 'If an account exists for that email address, a password reset code will be sent shortly.');
    }
}
