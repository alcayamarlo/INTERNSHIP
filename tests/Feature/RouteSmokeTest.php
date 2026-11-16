<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/*
 * Smoke test: every GET page without route parameters loads without a server error for its role,
 * on an empty database (so empty states are covered too). It does not check page content.
 */

function smokeUser(UserRole $role): User
{
    $institution = Institution::firstOrCreate(['name' => 'Smoke University']);
    $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
    match ($role) {
        UserRole::Student => Student::create([
            'user_id' => $user->id, 'institution_id' => $institution->id, 'program' => 'BS Information Technology',
            'first_name' => 'Smoke', 'last_name' => 'Student', 'privacy_consent' => true, 'privacy_consent_at' => now(),
        ]),
        UserRole::Employer => Employer::create(['user_id' => $user->id, 'company_name' => 'Smoke Co', 'contact_person' => 'Sam Smoke', 'verified_at' => now()]),
        UserRole::Coordinator => Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id]),
        default => null,
    };

    return $user;
}

/** @return list<string> */
function smokeUris(string $prefix): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => ($uri === $prefix || str_starts_with($uri, $prefix.'/')) && ! str_contains($uri, '{'))
        // Downloads and exports are covered by their own tests.
        ->reject(fn (string $uri) => preg_match('#(export|download|backup|logs/download)#', $uri))
        ->unique()->values()->all();
}

dataset('roles', [
    'admin' => [UserRole::Admin, 'admin'],
    'coordinator' => [UserRole::Coordinator, 'coordinator'],
    'employer' => [UserRole::Employer, 'employer'],
    'student' => [UserRole::Student, 'student'],
]);

it('loads every page of a role without a server error', function (UserRole $role, string $prefix) {
    Mail::fake();
    $user = smokeUser($role);
    $failures = [];

    foreach (smokeUris($prefix) as $uri) {
        $status = $this->actingAs($user)->get('/'.$uri)->getStatusCode();
        if ($status >= 500) {
            $failures[] = "/{$uri} -> {$status}";
        }
    }

    expect($failures)->toBe([]);
})->with('roles');

it('loads the shared signed-in pages and the public pages without a server error', function () {
    Mail::fake();
    $failures = [];
    foreach (['/', '/how-it-works', '/students', '/coordinators', '/faqs', '/login', '/forgot-password'] as $uri) {
        $status = $this->get($uri)->getStatusCode();
        if ($status >= 500) {
            $failures[] = "guest {$uri} -> {$status}";
        }
    }
    foreach ([UserRole::Admin, UserRole::Coordinator, UserRole::Employer, UserRole::Student] as $role) {
        $user = smokeUser($role);
        foreach (['/notifications', '/messages', '/profile', '/settings'] as $uri) {
            $status = $this->actingAs($user)->get($uri)->getStatusCode();
            if ($status >= 500) {
                $failures[] = "{$role->value} {$uri} -> {$status}";
            }
        }
    }

    expect($failures)->toBe([]);
});
