<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records application status changes in a full history timeline', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $employerUser = User::factory()->create([
        'role' => UserRole::Employer,
    ]);

    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Test Labs',
        'industry' => 'Technology',
        'contact_person' => 'Jane Employer',
    ]);

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'student_id_number' => '2025001',
        'program' => 'BS Computer Science',
    ]);

    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Software Engineering Intern',
        'description' => 'Build and test features.',
        'location' => 'Manila',
        'status' => 'open',
    ]);

    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Submitted,
        'cover_letter' => 'I am excited to apply.',
        'match_percentage' => 88,
    ]);

    $this->actingAs($employerUser)
        ->put(route('employer.applicants.status', $application), [
            'status' => ApplicationStatus::Reviewed->value,
            'employer_notes' => 'Candidate looks promising.',
            'interview_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHas('success', 'Application status updated.');

    $this->assertDatabaseHas('application_status_histories', [
        'application_id' => $application->id,
        'status' => ApplicationStatus::Reviewed->value,
        'actor_id' => $employerUser->id,
        'notes' => 'Candidate looks promising.',
    ]);
});
