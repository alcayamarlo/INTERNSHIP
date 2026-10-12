<?php

use App\Enums\UserRole;
use App\Jobs\SendNotification;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\User;
use App\Services\InternshipEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function phaseFourStudent(Institution $institution, array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-21-00013240',
        'program' => 'Computer Science',
        'year_level' => '3rd Year',
        ...$attributes,
    ]);
}

function phaseFourCoordinator(Institution $institution): array
{
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    return [$user, $coordinator];
}

function phaseFourProgram(Coordinator $coordinator, array $attributes = []): Program
{
    return Program::create([
        'name' => 'Computer Science',
        'code' => 'BSCS',
        'required_internship_hours' => 120,
        'required_hours_confirmed' => true,
        'coordinator_id' => $coordinator->id,
        ...$attributes,
    ]);
}

it('shows required and coordinator-rendered internship hours on the student profile', function () {
    $institution = Institution::create(['name' => 'Hours University']);
    [, $coordinator] = phaseFourCoordinator($institution);
    $program = phaseFourProgram($coordinator);
    $student = phaseFourStudent($institution, [
        'program_id' => $program->id,
        'rendered_internship_hours' => 45,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('Internship hours')
        ->assertSee('45 / 120 hours required');
});

it('prevents students from changing verified student ID program or year level through profile save', function () {
    $institution = Institution::create(['name' => 'Verified University']);
    [, $coordinator] = phaseFourCoordinator($institution);
    $currentProgram = phaseFourProgram($coordinator);
    $otherProgram = phaseFourProgram($coordinator, [
        'name' => 'Information Technology',
        'code' => 'BSIT',
    ]);
    $student = phaseFourStudent($institution, [
        'program_id' => $currentProgram->id,
        'profile_verified_at' => now(),
        'profile_verified_by_user_id' => $coordinator->user_id,
    ]);

    $this->actingAs($student->user)
        ->put(route('student.profile.update'), [
            'first_name' => 'Verified',
            'last_name' => 'Student',
            'email' => $student->user->email,
            'phone' => '09171234567',
            'address' => '1 Campus Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'zip_code' => '6000',
            'student_number' => 'SCC-22-00000001',
            'program_id' => $otherProgram->id,
            'year_level' => '4th Year',
        ])
        ->assertSessionHasErrors(['student_number', 'program_id', 'year_level']);

    expect($student->fresh()->student_id_number)->toBe('SCC-21-00013240')
        ->and($student->fresh()->program_id)->toBe($currentProgram->id)
        ->and($student->fresh()->year_level)->toBe('3rd Year');
});

it('allows a coordinator to verify a profile and update internship readiness', function () {
    $institution = Institution::create(['name' => 'Readiness University']);
    [$coordinatorUser, $coordinator] = phaseFourCoordinator($institution);
    $program = phaseFourProgram($coordinator);
    $student = phaseFourStudent($institution, ['program_id' => $program->id]);

    $this->actingAs($coordinatorUser)
        ->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSee('Academic progress and profile verification')
        ->assertSee('Rendered internship hours');

    $this->actingAs($coordinatorUser)
        ->post(route('coordinator.students.verify-profile', $student))
        ->assertRedirect();

    expect($student->fresh()->profile_verified_at)->not->toBeNull();

    $this->actingAs($coordinatorUser)
        ->put(route('coordinator.students.academic-readiness.update', $student), [
            'rendered_internship_hours' => 75,
            'major_subjects_passed' => 1,
        ])
        ->assertRedirect();

    expect($student->fresh()->rendered_internship_hours)->toBe(75)
        ->and($student->fresh()->major_subjects_passed)->toBeTrue();
});

it('notifies the assigned coordinator and applies an approved student change request', function () {
    Queue::fake();
    $institution = Institution::create(['name' => 'Change Request University']);
    [$coordinatorUser, $coordinator] = phaseFourCoordinator($institution);
    $program = phaseFourProgram($coordinator);
    $student = phaseFourStudent($institution, [
        'program_id' => $program->id,
        'profile_verified_at' => now(),
        'profile_verified_by_user_id' => $coordinatorUser->id,
    ]);

    $this->actingAs($student->user)
        ->post(route('student.profile.change-requests.store'), [
            'field' => 'student_id_number',
            'requested_value' => 'scc-19-00012320',
        ])
        ->assertRedirect();

    $changeRequest = StudentChangeRequest::query()->firstOrFail();
    expect($changeRequest->requested_value)->toBe('SCC-19-00012320');
    Queue::assertPushed(SendNotification::class, fn (SendNotification $job) => $job->userId === $coordinatorUser->id);

    $this->actingAs($coordinatorUser)
        ->patch(route('coordinator.students.change-requests.review', [$student, $changeRequest]), [
            'decision' => 'approved',
        ])
        ->assertRedirect();

    expect($student->fresh()->student_id_number)->toBe('SCC-19-00012320')
        ->and($changeRequest->fresh()->status)->toBe('approved');
    Queue::assertPushed(SendNotification::class, fn (SendNotification $job) => $job->userId === $student->user_id
        && $job->type === 'profile_change_request');
});

it('requires a coordinator note when rejecting a change request and sends the outcome', function () {
    Queue::fake();
    $institution = Institution::create(['name' => 'Review University']);
    [$coordinatorUser, $coordinator] = phaseFourCoordinator($institution);
    $program = phaseFourProgram($coordinator);
    $student = phaseFourStudent($institution, [
        'program_id' => $program->id,
        'profile_verified_at' => now(),
        'profile_verified_by_user_id' => $coordinatorUser->id,
    ]);
    $changeRequest = $student->changeRequests()->create([
        'field' => 'year_level',
        'current_value' => '3rd Year',
        'requested_value' => '4th Year',
        'status' => 'pending',
    ]);

    $this->actingAs($coordinatorUser)
        ->patch(route('coordinator.students.change-requests.review', [$student, $changeRequest]), [
            'decision' => 'rejected',
        ])
        ->assertSessionHasErrors('coordinator_note');

    $this->patch(route('coordinator.students.change-requests.review', [$student, $changeRequest]), [
        'decision' => 'rejected',
        'coordinator_note' => 'Please provide your updated enrollment record.',
    ])->assertRedirect();

    expect($changeRequest->fresh()->status)->toBe('rejected')
        ->and($changeRequest->fresh()->coordinator_note)->toBe('Please provide your updated enrollment record.');
    Queue::assertPushed(SendNotification::class, fn (SendNotification $job) => $job->userId === $student->user_id
        && $job->type === 'profile_change_request');
});

it('enforces only the enabled application eligibility requirements', function () {
    $settings = AcademicSetting::current();
    $student = new Student([
        'year_level' => '3rd Year',
        'major_subjects_passed' => false,
    ]);

    expect(app(InternshipEligibility::class)->reasons($student))->toBe([
        'You must be in 4th Year to apply.',
        'Your coordinator must confirm that all major subjects are passed.',
    ]);

    $settings->update([
        'require_fourth_year_standing' => false,
        'require_major_subjects_passed' => false,
    ]);

    expect(app(InternshipEligibility::class)->reasons($student))->toBe([]);
});

it('blocks internship applications while the configured eligibility requirements are unmet', function () {
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $studentUser->id,
        'year_level' => '3rd Year',
        'major_subjects_passed' => false,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Eligibility Test Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Eligibility Test Internship',
        'description' => 'An internship eligibility test.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    $this->actingAs($studentUser)
        ->from(route('student.internships.show', $internship))
        ->post(route('student.internships.apply', $internship), ['cover_letter' => 'Please consider my application.'])
        ->assertRedirect(route('student.internships.show', $internship))
        ->assertSessionHas('error', 'You must be in 4th Year to apply.');

    expect(InternshipApplication::query()->count())->toBe(0);
});
