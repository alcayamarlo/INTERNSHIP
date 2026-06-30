<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    /**
     * Display the registration form with available institutions and roles.
     */
    public function showRegistrationForm()
    {
        return view('auth.register', [
            'institutions' => Institution::orderBy('name')->get(),
            'roles' => [
                UserRole::Student,
                UserRole::Employer,
                UserRole::Coordinator,
            ],
        ]);
    }

    /**
     * Handle user registration with role-specific profile creation.
     *
     * Validates input, creates user account with appropriate role,
     * creates related profile (Student/Employer/Coordinator),
     * logs the action, and redirects to role-specific dashboard.
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
            ]);

            // Create role-specific profile
            match (UserRole::from($validated['role'])) {
                UserRole::Student => Student::create([
                    'user_id' => $user->id,
                    'institution_id' => $validated['institution_id'] ?? null,
                    'program' => $validated['program'] ?? null,
                    'profile_completion' => 20,
                ]),
                UserRole::Employer => Employer::create([
                    'user_id' => $user->id,
                    'company_name' => $validated['company_name'],
                    'contact_person' => $validated['name'],
                ]),
                UserRole::Coordinator => Coordinator::create([
                    'user_id' => $user->id,
                    'institution_id' => $validated['institution_id'] ?? null,
                ]),
                default => null,
            };

            return $user;
        });

        Auth::login($user);
        $this->activityLog->log($user, 'register', ['role' => $user->role->value]);

        return redirect()->route($user->dashboardRoute())
            ->with('status', 'Welcome! Your account has been successfully created.');
    }
}
