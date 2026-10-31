<?php

use App\Enums\UserRole;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('forces a password change on first login via school ID, then unlocks the dashboard', function () {
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null]);
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'password' => Hash::make('05142003'),
        'email_verified_at' => now(),
        'must_change_password' => true,
    ]);
    Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000009',
    ]);

    $this->post('/login', [
        'login' => 'scc-26-00000009',
        'password' => '05142003',
    ])->assertRedirect(route('password.force-change'));

    $this->get('/student/dashboard')->assertRedirect(route('password.force-change'));

    $this->put(route('password.force-change.update'), [
        'password' => 'NewSecurePassword1',
        'password_confirmation' => 'NewSecurePassword1',
    ])->assertRedirect(route('student.dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse();

    $this->get('/student/dashboard')->assertOk();
});

it('rejects an unknown school ID and wrong password with the renamed login field', function () {
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null]);
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'password' => Hash::make('05142003'),
        'email_verified_at' => now(),
    ]);
    Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000010',
    ]);

    $this->from('/login')->post('/login', [
        'login' => 'SCC-26-00000099',
        'password' => '05142003',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => 'Account not found.']);

    $this->from('/login')->post('/login', [
        'login' => 'SCC-26-00000010',
        'password' => 'WrongPassword',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => 'Wrong password.']);

    $this->assertGuest();
});

it('still logs in existing roles by email unchanged', function () {
    $employer = User::factory()->create([
        'role' => UserRole::Employer,
        'email' => 'employer.schoolid-regression@example.test',
        'password' => 'Password',
    ]);

    $this->post('/login', [
        'login' => $employer->email,
        'password' => 'Password',
    ])->assertRedirect(route('employer.dashboard'));
});
