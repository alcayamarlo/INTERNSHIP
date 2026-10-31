<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('logs a user in and logs them out', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => 'login-test@example.test',
        'password' => 'TestPassword#2026',
    ]);
    Student::create(['user_id' => $user->id]);

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'TestPassword#2026',
    ])->assertRedirect(route('student.dashboard'));

    expect(Auth::check())->toBeTrue();

    $session = $this->app['session.store'];
    $sessionId = $session->getId();
    $csrfToken = $session->token();

    $this->post(route('logout'))->assertRedirect(route('home'));
    expect(Auth::check())->toBeFalse();
    expect($session->getId())->not->toBe($sessionId)
        ->and($session->token())->not->toBe($csrfToken);
});

it('logs in each role to its own dashboard and keeps the session authenticated on refresh', function () {
    $this->withoutVite();

    foreach (UserRole::cases() as $role) {
        $user = User::factory()->create([
            'role' => $role,
            'email' => "{$role->value}@example.test",
            'password' => 'TestPassword#2026',
        ]);

        match ($role) {
            UserRole::Student => Student::create(['user_id' => $user->id]),
            UserRole::Employer => Employer::create([
                'user_id' => $user->id,
                'company_name' => 'Test Company',
                'contact_person' => $user->name,
            ]),
            UserRole::Coordinator => Coordinator::create(['user_id' => $user->id]),
            UserRole::Admin => null,
        };

        $dashboard = route($role->dashboardRoute());

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'TestPassword#2026',
        ])->assertRedirect($dashboard);

        $this->get($dashboard)->assertOk();
        $this->get($dashboard)->assertOk();

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }
});

it('rejects incorrect and nonexistent login credentials with a validation message', function () {
    $user = User::factory()->create([
        'email' => 'valid-login@example.test',
        'password' => 'TestPassword#2026',
    ]);

    foreach ([
        ['login' => $user->email, 'password' => 'IncorrectPassword#2026'],
        ['login' => 'missing-login@example.test', 'password' => 'TestPassword#2026'],
    ] as $credentials) {
        $this->post(route('login'), $credentials)
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }
});

it('does not authenticate inactive users', function () {
    $user = User::factory()->create([
        'email' => 'inactive-login@example.test',
        'password' => 'TestPassword#2026',
        'is_active' => false,
    ]);

    $this->post(route('login'), [
        'login' => $user->email,
        'password' => 'TestPassword#2026',
    ])->assertSessionHasErrors([
        'login' => 'Your account has been deactivated. Please contact support.',
    ]);

    $this->assertGuest();
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
