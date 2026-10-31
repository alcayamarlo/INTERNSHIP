<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForcePasswordChangeRequest;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChangeController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function edit()
    {
        return view('auth.force-password-change');
    }

    public function update(ForcePasswordChangeRequest $request)
    {
        $user = Auth::user();

        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ])->save();

        $this->activityLog->log($user, 'password_force_change');

        return redirect()->route($user->dashboardRoute())->with('success', 'Your password has been updated.');
    }
}
