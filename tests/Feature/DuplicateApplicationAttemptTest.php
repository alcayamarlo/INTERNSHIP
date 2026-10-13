<?php

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects duplicate applications for the same internship and student', function () {
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
    ]);
    $resume = Resume::create(['student_id' => $student->id, 'content' => []]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Duplicate Application Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Duplicate Test Internship',
        'description' => 'A duplicate application test.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    $this->actingAs($studentUser)
        ->post(route('student.internships.apply', $internship), ['resume_id' => $resume->id, 'cover_letter' => 'First application.'])
        ->assertRedirect(route('student.applications.index'));

    $this->from(route('student.internships.show', $internship))
        ->post(route('student.internships.apply', $internship), ['resume_id' => $resume->id, 'cover_letter' => 'Duplicate application.'])
        ->assertRedirect(route('student.internships.show', $internship))
        ->assertSessionHas('error', 'You have already applied for this internship.');

    expect(InternshipApplication::where('student_id', $student->id)
        ->where('internship_id', $internship->id)
        ->count())->toBe(1);
});

it('submits internship applications asynchronously', function () {
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
    ]);
    $resume = Resume::create(['student_id' => $student->id, 'content' => []]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Async Application Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Async Test Internship',
        'description' => 'An asynchronous application test.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    $this->actingAs($studentUser)
        ->postJson(route('student.internships.apply', $internship), ['resume_id' => $resume->id, 'cover_letter' => 'I would like to apply.'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 'applied')
        ->assertJsonStructure(['applications_url']);

    $this->assertDatabaseHas('internship_applications', [
        'student_id' => $studentUser->student->id,
        'internship_id' => $internship->id,
        'resume_id' => $resume->id,
        'status' => 'applied',
    ]);
});
