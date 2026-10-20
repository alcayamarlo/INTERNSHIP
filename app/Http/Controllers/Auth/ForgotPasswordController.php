<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\MailDeliveryFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Jobs\SendPasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    /**
     * Display the forgot password form.
     */
    public function showResetForm(Request $request)
    {
        if ($request->boolean('new')) {
            $request->session()->forget([
                'password_reset_code_requested',
                'password_reset_code_expires_at',
            ]);
        }

        return view('auth.forgot-password', [
            'showResetFields' => (bool) $request->session()->get('password_reset_code_requested'),
            'resetCodeExpiresAt' => $request->session()->get('password_reset_code_expires_at'),
        ]);
    }

    /**
     * Queue the same reset-code job for known and unknown email addresses.
     */
    public function sendResetCode(ForgotPasswordRequest $request)
    {
        $email = $request->validated('email');

        $request->session()->forget([
            'password_reset_code_requested',
            'password_reset_code_expires_at',
        ]);

        try {
            Bus::dispatchSync(new SendPasswordResetCode($email));
        } catch (MailDeliveryFailed|TransportExceptionInterface $exception) {
            // MailSender has already logged the transport error.
            return redirect()
                ->route('password.request')
                ->withInput(['email' => $email])
                ->withErrors(['email' => "We couldn't send the email. Please try again later."]);
        }

        $request->session()->put([
            'password_reset_code_requested' => true,
            'password_reset_code_expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        return redirect()
            ->route('password.request')
            ->withInput(['email' => $email])
            ->with('status', 'If an account exists for that email address, a password reset code is on its way.');
    }
}
