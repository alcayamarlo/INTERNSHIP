<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeResumeAccessScenario(ApplicationStatus $status = ApplicationStatus::Submitted): array
{
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Resume Access Employer',
        'contact_person' => 'Reviewer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Resume Access Internship',
        'description' => 'Review resume access.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => $status,
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);

    $path = 'resumes/'.$student->id.'/access-test.pdf';
    Storage::disk('private')->put($path, '%PDF-1.4 test resume');
    $resume = Resume::create([
        'student_id' => $student->id,
        'file_path' => $path,
        'generated_at' => now(),
    ]);

    return compact('studentUser', 'student', 'employerUser', 'employer', 'application', 'resume', 'path');
}

it('lets the employer open the student\'s latest resume through an application', function () {
    Storage::fake('private');
    $scenario = makeResumeAccessScenario(ApplicationStatus::Interview);

    $this->actingAs($scenario['employerUser'])
        ->get(route('employer.applicants.show', $scenario['application']))
        ->assertOk()
        ->assertSee('View resume');

    $this->get(route('employer.applicants.resume.preview', $scenario['application']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('blocks the employer once the application is withdrawn', function () {
    Storage::fake('private');
    $scenario = makeResumeAccessScenario();
    $scenario['application']->update(['status' => ApplicationStatus::Withdrawn]);

    $this->actingAs($scenario['employerUser'])
        ->get(route('employer.applicants.resume.preview', $scenario['application']))
        ->assertForbidden();
});

it('does not expose resume file paths on the employer applicant list', function () {
    Storage::fake('private');
    $scenario = makeResumeAccessScenario();

    $this->actingAs($scenario['employerUser'])
        ->get(route('employer.applicants.index'))
        ->assertOk()
        ->assertDontSee($scenario['path']);
});

it('prevents another student from viewing or deleting a resume', function () {
    Storage::fake('private');
    $scenario = makeResumeAccessScenario();
    $anotherStudentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $anotherStudentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $this->actingAs($anotherStudentUser)
        ->get(route('student.resume.view', $scenario['resume']))
        ->assertForbidden();
    $this->deleteJson(route('student.resume.destroy', $scenario['resume']))
        ->assertForbidden();

    expect(Resume::find($scenario['resume']->id))->not->toBeNull()
        ->and(Storage::disk('private')->exists($scenario['path']))->toBeTrue();
});
