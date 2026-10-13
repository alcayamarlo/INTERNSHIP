<?php

use App\Enums\ApplicationStatus;
use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Mail\InterviewInvitationMail;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Models\AppNotification;

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
        'student_id_number' => 'SCC-25-00000001',
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

    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Verified Employer Skill',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);
    Certificate::create([
        'student_id' => $student->id,
        'title' => 'Verified Employer Certificate',
        'issuer' => 'Test Issuer',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSee('Verified Employer Skill')
        ->assertSee('Verified Employer Certificate')
        ->assertSee('bi-patch-check-fill');

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

it('requires an interview time when an employer marks an application as interview', function () {
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
        'student_id_number' => 'SCC-25-00000002',
        'program' => 'BS Computer Science',
    ]);

    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'QA Automation Intern',
        'description' => 'Write tests for our products.',
        'location' => 'Cebu',
        'status' => 'open',
    ]);

    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Reviewed,
        'cover_letter' => 'I would love to interview.',
        'match_percentage' => 91,
    ]);

    $this->actingAs($employerUser)
        ->from(route('employer.applicants.show', $application))
        ->put(route('employer.applicants.status', $application), [
            'status' => ApplicationStatus::Interview->value,
            'employer_notes' => 'Please attend the interview.',
        ])
        ->assertSessionHasErrors(['interview_at'])
        ->assertSessionHasErrors(['interview_at' => 'Interview date is required when moving to the interview stage.']);
});

it('sends an interview calendar invite when an employer schedules an interview', function () {
    Mail::fake();

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
        'email' => 'student-interview@example.com',
    ]);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-25-00000003',
        'program' => 'BS Computer Science',
    ]);

    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Product Design Intern',
        'description' => 'Help design and prototype user flows.',
        'location' => 'Davao',
        'status' => 'open',
    ]);

    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Reviewed,
        'cover_letter' => 'I am eager to interview.',
        'match_percentage' => 90,
    ]);

    $this->actingAs($employerUser)
        ->put(route('employer.applicants.status', $application), [
            'status' => ApplicationStatus::Interview->value,
            'employer_notes' => 'Please join our interview.',
            'interview_at' => now()->addDay()->setTime(14, 30)->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHas('success', 'Application status updated.');

    Mail::assertSent(InterviewInvitationMail::class, function (InterviewInvitationMail $mail) use ($studentUser, $application) {
        return $mail->hasTo($studentUser->email)
            && $mail->subject === 'Interview invitation for '.$application->internship->title
            && str_contains($mail->icsContent, 'BEGIN:VCALENDAR')
            && str_contains($mail->icsContent, 'SUMMARY:Interview for '.$application->internship->title);
    });
});

function createApplicationStatusTransitionFixture(ApplicationStatus $status): array
{
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Transition Test Company',
        'contact_person' => 'Transition Employer',
    ]);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Status Transition Intern',
        'description' => 'Test status transitions.',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => $status,
        'match_percentage' => 50,
    ]);

    return [$employerUser, $application];
}

it('allows every configured employer status transition and records each change in history', function () {
    Mail::fake();
    $allowedTransitionCount = 0;

    foreach (ApplicationStatus::cases() as $currentStatus) {
        foreach ($currentStatus->allowedNextStatuses() as $nextStatus) {
            [$employerUser, $application] = createApplicationStatusTransitionFixture($currentStatus);
            $payload = ['status' => $nextStatus->value];

            if ($nextStatus === ApplicationStatus::Interview) {
                $payload['interview_at'] = now()->addDay()->format('Y-m-d\TH:i');
            }

            $this->actingAs($employerUser)
                ->put(route('employer.applicants.status', $application), $payload)
                ->assertSessionHas('success', 'Application status updated.');

            expect($application->fresh()->status)->toBe($nextStatus);
            $this->assertDatabaseHas('application_status_histories', [
                'application_id' => $application->id,
                'actor_id' => $employerUser->id,
                'status' => $nextStatus->value,
            ]);
            $allowedTransitionCount++;
        }
    }

    expect($allowedTransitionCount)->toBe(15);
});

it('rejects every employer status transition not present in the configured map', function () {
    foreach (ApplicationStatus::cases() as $currentStatus) {
        [$employerUser, $application] = createApplicationStatusTransitionFixture($currentStatus);

        foreach (ApplicationStatus::cases() as $nextStatus) {
            if ($currentStatus->canTransitionTo($nextStatus)) {
                continue;
            }

            $this->actingAs($employerUser)
                ->from(route('employer.applicants.show', $application))
                ->put(route('employer.applicants.status', $application), [
                    'status' => $nextStatus->value,
                    'interview_at' => now()->addDay()->format('Y-m-d\TH:i'),
                ])
                ->assertRedirect(route('employer.applicants.show', $application))
                ->assertSessionHasErrors([
                    'status' => 'That status transition is not allowed from the current application status.',
                ]);

            expect($application->fresh()->status)->toBe($currentStatus)
                ->and($application->statusHistory()->count())->toBe(0);
        }
    }
});

it('shows only allowed next statuses in the employer dropdown', function () {
    foreach (ApplicationStatus::cases() as $currentStatus) {
        [$employerUser, $application] = createApplicationStatusTransitionFixture($currentStatus);

        $response = $this->actingAs($employerUser)
            ->get(route('employer.applicants.show', $application))
            ->assertOk();

        foreach ($currentStatus->allowedNextStatuses() as $nextStatus) {
            $response->assertSee('<option value="'.$nextStatus->value.'"', false);
        }

        foreach (ApplicationStatus::cases() as $nextStatus) {
            if (! $currentStatus->canTransitionTo($nextStatus)) {
                $response->assertDontSee('<option value="'.$nextStatus->value.'"', false);
            }
        }

        if ($currentStatus->allowedNextStatuses() === []) {
            $response->assertSee('No further status changes are available.');
        }
    }
});

it('updates hire and reject decisions asynchronously and notifies students', function () {
    config(['queue.default' => 'sync', 'mail.default' => 'log']);

    [$employerUser, $hireApplication] = createApplicationStatusTransitionFixture(ApplicationStatus::Reviewed);
    $studentUser = $hireApplication->student->user;

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $hireApplication))
        ->assertOk()
        ->assertSee('data-ajax-form data-application-status-form', false);

    $this->actingAs($employerUser)
        ->putJson(route('employer.applicants.status', $hireApplication), [
            'status' => ApplicationStatus::Accepted->value,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', ApplicationStatus::Accepted->value)
        ->assertJsonPath('status_label', ApplicationStatus::Accepted->label())
        ->assertJsonPath('available_statuses.0.value', ApplicationStatus::Completed->value);

    expect($hireApplication->fresh()->status)->toBe(ApplicationStatus::Accepted);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $studentUser->id,
        'type' => 'application_update',
    ]);

    $this->actingAs($studentUser)
        ->get(route('student.applications.index'))
        ->assertOk()
        ->assertSee(ApplicationStatus::Accepted->label());

    [$otherEmployerUser, $rejectApplication] = createApplicationStatusTransitionFixture(ApplicationStatus::Applied);
    $rejectedStudent = $rejectApplication->student->user;

    $this->actingAs($otherEmployerUser)
        ->putJson(route('employer.applicants.status', $rejectApplication), [
            'status' => ApplicationStatus::Rejected->value,
        ])
        ->assertOk()
        ->assertJsonPath('status', ApplicationStatus::Rejected->value)
        ->assertJsonPath('available_statuses', []);

    expect($rejectApplication->fresh()->status)->toBe(ApplicationStatus::Rejected);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $rejectedStudent->id,
        'type' => 'application_update',
    ]);

    $this->actingAs($rejectedStudent)
        ->get(route('student.applications.index'))
        ->assertOk()
        ->assertSee(ApplicationStatus::Rejected->label());
});

it('does not allow another employer to change the application status', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $ownerEmployer = Employer::create([
        'user_id' => $owner->id,
        'company_name' => 'Application Owner',
        'contact_person' => 'Owner',
    ]);
    $otherEmployerUser = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $otherEmployerUser->id,
        'company_name' => 'Different Employer',
        'contact_person' => 'Other',
    ]);
    $student = Student::create([
        'user_id' => User::factory()->create(['role' => UserRole::Student])->id,
    ]);
    $internship = Internship::create([
        'employer_id' => $ownerEmployer->id,
        'title' => 'Owner Internship',
        'description' => 'Owned by another employer.',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Submitted,
        'match_percentage' => 50,
    ]);

    $this->actingAs($otherEmployerUser)
        ->put(route('employer.applicants.status', $application), [
            'status' => ApplicationStatus::Reviewed->value,
        ])
        ->assertForbidden();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->statusHistory()->count())->toBe(0);
});
