<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;

class ProfileCompletionController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $profileRoute = match ($user->role) {
            UserRole::Student => 'student.profile.edit',
            UserRole::Employer => 'employer.profile.edit',
            UserRole::Coordinator => 'coordinator.profile.edit',
            UserRole::Admin => $user->dashboardRoute(),
        };

        return view('profile.complete', [
            'role' => $user->role,
            'profileRoute' => $profileRoute,
        ]);
    }
}
