<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Two-factor sign-in settings: status, recovery codes (shown once), switching it on or off, trusted browsers. */
class AccountSecurityController extends Controller
{
    public function __construct(private TwoFactor $twoFactor, private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('account.security', [
            'required' => $this->twoFactor->isRequiredFor($user),
            'optional' => $this->twoFactor->canEnable($user),
            'enabled' => $this->twoFactor->isActiveFor($user),
            'canEmail' => $this->twoFactor->canEmail($user),
            'skippedHere' => $this->twoFactor->skippedHere(),
            'codesLeft' => $this->twoFactor->recoveryCodesLeft($user),
            'newCodes' => session('recovery_codes'), // only right after they were made
            'browsers' => DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->where('expires_at', '>', now())->orderByDesc('created_at')->get(['label', 'created_at', 'last_used_at', 'expires_at']),
        ]);
    }

    /** Turns it on for a coordinator, HR or supervisor, and shows their recovery codes once. */
    public function enable(Request $request)
    {
        $this->confirmPassword($request);
        $user = $request->user();
        abort_unless($this->twoFactor->canEnable($user), 403);
        if (! $this->twoFactor->canEmail($user)) {
            return back()->withErrors(['password' => 'Add and verify a real email address first: sign-in codes are sent there.']);
        }

        $user->forceFill(['two_factor_enabled' => true])->saveQuietly();
        $this->activityLog->log($user, 'two_factor_enabled');

        return redirect()->route('account.security')->with('recovery_codes', $this->twoFactor->generateRecoveryCodes($user))
            ->with('success', 'Two-factor sign-in is on. Save your recovery codes below: they are shown only once.');
    }

    public function disable(Request $request)
    {
        $this->confirmPassword($request);
        $user = $request->user();
        abort_unless($this->twoFactor->canEnable($user), 403); // admins cannot turn it off

        $user->forceFill(['two_factor_enabled' => false, 'two_factor_recovery_codes' => null])->saveQuietly();
        $this->twoFactor->forgetBrowsers($user);
        $this->activityLog->log($user, 'two_factor_disabled');

        return redirect()->route('account.security')->with('success', 'Two-factor sign-in is off.');
    }

    /** New recovery codes replace the old ones, which stop working. */
    public function recoveryCodes(Request $request)
    {
        $this->confirmPassword($request);
        $user = $request->user();
        abort_unless($this->twoFactor->isActiveFor($user), 403);

        return redirect()->route('account.security')->with('recovery_codes', $this->twoFactor->generateRecoveryCodes($user))
            ->with('success', 'New recovery codes made. The old ones no longer work. Save these: they are shown only once.');
    }

    public function forgetBrowsers(Request $request)
    {
        $removed = $this->twoFactor->forgetBrowsers($request->user());

        return redirect()->route('account.security')->with('success', $removed === 0 ? 'No browsers were remembered.' : 'Every browser will ask for a code again.');
    }

    private function confirmPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'current_password']], ['password.current_password' => 'That is not your current password.']);
    }
}
