<?php

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUserUpdateData(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role->value,
        'is_active' => $user->is_active,
    ], $overrides);
}

it('allows admins to create a student account with its student profile', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $institution = Institution::create(['name' => 'Test Institution', 'address' => 'Cebu City']);

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('value="student"', false)
        ->assertDontSee('value="admin"', false)
        ->assertSee('Student');

    $this->post(route('admin.users.store'), [
        'name' => 'Admin-created Student',
        'email' => 'admin-created-student@example.test',
        'password' => 'DistinctivePassword#2026',
        'password_confirmation' => 'DistinctivePassword#2026',
        'role' => UserRole::Student->value,
        'institution_id' => $institution->id,
    ])->assertRedirect(route('admin.users.index'))
        ->assertSessionHasNoErrors();

    $studentUser = User::where('email', 'admin-created-student@example.test')->firstOrFail();
    $student = $studentUser->student;

    expect($studentUser->role)->toBe(UserRole::Student)
        ->and($student)->toBeInstanceOf(Student::class)
        ->and($student->institution_id)->toBe($institution->id)
        ->and($student->profile_completion)->toBe($student->profileCompletionSummary()['percentage']);
});

it('uses all roles in user filters, limits announcement audiences, and keeps admin creation restricted', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('value="student"', false)
        ->assertSee('value="admin"', false)
        ->assertSee('value="employer"', false)
        ->assertSee('value="coordinator"', false);

    $this->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('value="student"', false)
        ->assertSee('value="employer"', false)
        ->assertSee('value="coordinator"', false)
        ->assertDontSee('value="admin"', false)
        ->assertSee('value="all"', false);

    $this->post(route('admin.users.store'), [
        'name' => 'Attempted Administrator',
        'email' => 'attempted-admin@example.test',
        'password' => 'DistinctivePassword#2026',
        'password_confirmation' => 'DistinctivePassword#2026',
        'role' => UserRole::Admin->value,
    ])->assertSessionHasErrors('role');

    expect(User::where('email', 'attempted-admin@example.test')->exists())->toBeFalse();
});

it('creates the new role profile and preserves the previous profile when an admin changes a student to employer', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $studentUser), adminUserUpdateData($studentUser, [
            'role' => UserRole::Employer->value,
        ]))
        ->assertSessionHasNoErrors();

    $studentUser->refresh();

    expect($studentUser->role)->toBe(UserRole::Employer)
        ->and($student->fresh())->not->toBeNull()
        ->and($studentUser->employer)->toBeInstanceOf(Employer::class)
        ->and($studentUser->employer->company_name)->toBe($studentUser->name)
        ->and($studentUser->employer->contact_person)->toBe($studentUser->name);
});

it('redirects users with missing role profiles to profile completion instead of crashing the dashboard', function () {
    $studentUser = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($studentUser)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('profile.complete'));

    $this->get(route('profile.complete'))
        ->assertOk()
        ->assertSee('Complete your Student profile')
        ->assertSee(route('student.profile.edit'));

    $this->get(route('student.profile.edit'))
        ->assertOk();

    expect($studentUser->fresh()->student)->toBeInstanceOf(Student::class);
});

it('does not allow the last active admin to deactivate their account', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), adminUserUpdateData($admin, [
            'is_active' => false,
        ]))
        ->assertSessionHasErrors(['role', 'is_active']);

    expect($admin->fresh()->is_active)->toBeTrue()
        ->and($admin->fresh()->role)->toBe(UserRole::Admin);
});

it('does not allow an admin to change their own role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), adminUserUpdateData($admin, [
            'role' => UserRole::Student->value,
        ]))
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});
