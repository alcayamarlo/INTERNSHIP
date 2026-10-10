<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('registers a student and sends an email verification code', function () {
    Http::fake();
    Mail::fake();

    $institution = Institution::create(['name' => "St. Cecilia's College-Cebu, Inc."]);

    $this->post(route('register.store'), [
        'name' => 'Registration Test Student',
        'email' => 'registration-student@example.test',
        'password' => 'DistinctivePassword#2026',
        'password_confirmation' => 'DistinctivePassword#2026',
        'institution_id' => $institution->id,
        'program' => 'Bachelor of Science in Information Technology',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'registration-student@example.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Student)
        ->and($user->student)->toBeInstanceOf(Student::class)
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Mail::assertSent(EmailVerificationCodeMail::class, fn (EmailVerificationCodeMail $mail) => $mail->hasTo($user->email));
});

it('logs a user in and logs them out', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => 'login-test@example.test',
        'password' => 'TestPassword#2026',
    ]);
    Student::create(['user_id' => $user->id]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'TestPassword#2026',
    ])->assertRedirect(route('student.dashboard'));

    expect(Auth::check())->toBeTrue();

    $this->post(route('logout'))->assertRedirect(route('home'));
    expect(Auth::check())->toBeFalse();
});

it('denies every role access to dashboards belonging to other roles', function () {
    $dashboardRoutes = [
        UserRole::Student->value => [
            route('student.dashboard'),
            route('student.profile.edit'),
        ],
        UserRole::Employer->value => [
            route('employer.dashboard'),
            route('employer.internships.index'),
        ],
        UserRole::Coordinator->value => [
            route('coordinator.dashboard'),
            route('coordinator.reports.index'),
        ],
        UserRole::Admin->value => [
            route('admin.dashboard'),
            route('admin.users.index'),
        ],
    ];

    foreach (UserRole::cases() as $role) {
        $user = User::factory()->create(['role' => $role]);

        foreach ($dashboardRoutes as $routeRole => $urls) {
            if ($routeRole === $role->value) {
                continue;
            }

            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }
});
