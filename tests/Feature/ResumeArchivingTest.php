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

function makeResumeArchiveScenario(ApplicationStatus $status = ApplicationStatus::Submitted): array
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
        'company_name' => 'Resume Archive Employer',
        'contact_person' => 'Reviewer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Resume Archive Internship',
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

    $path = 'resumes/'.$student->id.'/archive-test.pdf';
    Storage::disk('private')->put($path, '%PDF-1.4 test resume');
    $resume = Resume::create([
        'student_id' => $student->id,
        'file_path' => $path,
        'generated_at' => now(),
    ]);

    return compact('studentUser', 'student', 'employerUser', 'employer', 'application', 'resume', 'path');
}

it('requires confirmation for an active application, archives without deleting, and restores the resume', function () {
    Storage::fake('private');
    $scenario = makeResumeArchiveScenario();

    $this->actingAs($scenario['studentUser'])
        ->get(route('student.resume.index'))
        ->assertOk()
        ->assertSee('data-ajax-resume="archive"', false);

    $this->postJson(route('student.resume.archive', $scenario['resume']->id))
        ->assertStatus(409)
        ->assertJsonPath('confirmation_required', true)
        ->assertJsonPath('active_application_count', 1)
        ->assertJsonPath(
            'message',
            'This resume is attached to 1 active application(s). Employers reviewing those applications will still be able to view it. Archive anyway?'
        );

    $this->postJson(route('student.resume.archive', $scenario['resume']->id), [
        'confirmed_active_applications' => '1',
    ])->assertOk()->assertJsonPath('success', true);

    expect($scenario['resume']->fresh()->archived_at)->not->toBeNull()
        ->and($scenario['application']->fresh()->status)->toBe(ApplicationStatus::Submitted)
        ->and(Storage::disk('private')->exists($scenario['path']))->toBeTrue();

    $this->get(route('student.resume.index'))
        ->assertOk()
        ->assertDontSee('archive-test.pdf');

    $this->get(route('student.resume.index', ['view' => 'archived']))
        ->assertOk()
        ->assertSee('Restore')
        ->assertSee('data-ajax-resume="restore"', false);

    $this->postJson(route('student.resume.restore', $scenario['resume']->id))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($scenario['resume']->fresh()->archived_at)->toBeNull();
    $this->get(route('student.resume.index'))
        ->assertOk()
        ->assertSee('View PDF');
});

it('lets the employer access the archived current resume only through its active application', function () {
    Storage::fake('private');
    $scenario = makeResumeArchiveScenario(ApplicationStatus::Interview);
    $scenario['resume']->update(['archived_at' => now()]);

    $this->actingAs($scenario['employerUser'])
        ->get(route('employer.applicants.show', $scenario['application']))
        ->assertOk()
        ->assertSee('View resume');

    $this->get(route('student.resume.view', $scenario['resume']->id))
        ->assertNotFound();

    $this->get(route('employer.applicants.resume.preview', $scenario['application']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $scenario['application']->update(['status' => ApplicationStatus::Accepted]);
    $this->get(route('employer.applicants.resume.preview', $scenario['application']))
        ->assertNotFound();
});

it('hides archived resumes from the employer applicant resume list', function () {
    Storage::fake('private');
    $scenario = makeResumeArchiveScenario();
    $scenario['resume']->update(['archived_at' => now()]);

    $this->actingAs($scenario['employerUser'])
        ->get(route('employer.applicants.index'))
        ->assertOk()
        ->assertDontSee(route('student.resume.view', $scenario['resume']->id, false), false)
        ->assertDontSee($scenario['path']);
});

it('prevents another student from archiving or restoring a resume', function () {
    Storage::fake('private');
    $scenario = makeResumeArchiveScenario();
    $anotherStudentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $anotherStudentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $this->actingAs($anotherStudentUser)
        ->postJson(route('student.resume.archive', $scenario['resume']->id))
        ->assertForbidden();

    $scenario['resume']->update(['archived_at' => now()]);
    $this->postJson(route('student.resume.restore', $scenario['resume']->id))
        ->assertForbidden();

    expect($scenario['resume']->fresh()->archived_at)->not->toBeNull();
});
