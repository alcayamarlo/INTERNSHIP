<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCoordinatorForInstitution(Institution $institution): User
{
    $user = User::factory()->create([
        'role' => UserRole::Coordinator,
    ]);

    Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    return $user;
}

function createStudentForInstitution(Institution $institution): Student
{
    $user = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    return Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'program' => 'BS Computer Science',
    ]);
}

it('lists students for the coordinator institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $otherInstitution = Institution::create([
        'name' => 'Other University',
        'address' => '456 Campus Rd',
        'contact_email' => 'info@other.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $visibleStudent = createStudentForInstitution($institution);
    createStudentForInstitution($otherInstitution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.index'))
        ->assertOk()
        ->assertSee($visibleStudent->user->name)
        ->assertDontSee('Other University');
});

it('allows coordinators to view students in their institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $student = createStudentForInstitution($institution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSee($student->user->name);
});

it('forbids coordinators from viewing students outside their institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $otherInstitution = Institution::create([
        'name' => 'Other University',
        'address' => '456 Campus Rd',
        'contact_email' => 'info@other.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $otherStudent = createStudentForInstitution($otherInstitution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.show', $otherStudent))
        ->assertForbidden();
});
