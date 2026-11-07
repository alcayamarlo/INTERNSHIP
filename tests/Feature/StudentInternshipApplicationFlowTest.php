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

uses(RefreshDatabase::class);

function studentApplicationFixture(array $internshipAttributes = []): array
{
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
        'company_name' => 'Application Flow Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Application Flow Internship',
        'description' => 'A test internship.',
        'work_setup' => 'remote',
        'status' => 'open',
        ...$internshipAttributes,
    ]);

    return compact('studentUser', 'student', 'resume', 'employerUser', 'employer', 'internship');
}

it('saves the chosen resume and shows the application to its student', function () {
    $fixture = studentApplicationFixture();

    $response = $this->actingAs($fixture['studentUser'])
        ->postJson(route('student.internships.apply', $fixture['internship']), [
            'resume_id' => $fixture['resume']->id,
            'cover_letter' => 'I would like to apply.',
        ]);

    $response->assertOk()->assertJsonPath('status', 'applied');
    $this->assertDatabaseHas('internship_applications', [
        'student_id' => $fixture['student']->id,
        'internship_id' => $fixture['internship']->id,
        'resume_id' => $fixture['resume']->id,
        'status' => 'applied',
    ]);

    $application = InternshipApplication::where('student_id', $fixture['student']->id)->firstOrFail();
    expect($application->applied_at)->not->toBeNull();

    $this->get(route('student.applications.index'))
        ->assertOk()
        ->assertSee('Application Flow Internship')
        ->assertSee('Application Flow Employer')
        ->assertSee('Applied');
});

it('rejects applications for closed and expired postings', function (array $attributes, string $message) {
    $fixture = studentApplicationFixture($attributes);

    $this->actingAs($fixture['studentUser'])
        ->postJson(route('student.internships.apply', $fixture['internship']), [
            'resume_id' => $fixture['resume']->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);

    $this->assertDatabaseCount('internship_applications', 0);
})->with([
    'closed' => [['status' => 'closed'], 'This internship is no longer accepting applications.'],
    'expired' => [['expires_at' => now()->subDay()], 'This internship posting has expired and is no longer accepting applications.'],
]);

it('does not offer or accept another student\'s resume', function () {
    $fixture = studentApplicationFixture();
    $other = studentApplicationFixture();

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.internships.show', $fixture['internship']))
        ->assertOk()
        ->assertDontSee('value="'.$other['resume']->id.'"', false);

    $this->postJson(route('student.internships.apply', $fixture['internship']), [
        'resume_id' => $other['resume']->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('resume_id');
});

it('limits the applications page and application details to their owning student', function () {
    $owner = studentApplicationFixture();
    $otherStudentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $otherStudentUser->id,
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $owner['internship']->id,
        'student_id' => $owner['student']->id,
        'resume_id' => $owner['resume']->id,
        'status' => ApplicationStatus::Applied,
        'applied_at' => now(),
    ]);

    $this->actingAs($otherStudentUser)
        ->get(route('student.applications.index'))
        ->assertOk()
        ->assertDontSee('Application Flow Internship');
    $this->get(route('student.applications.show', $application))->assertForbidden();

    $this->actingAs($owner['studentUser'])
        ->get(route('student.applications.show', $application))
        ->assertOk()
        ->assertSee('Application Flow Internship');
});

it('shows active resumes and inline apply actions in the internship listing', function () {
    $fixture = studentApplicationFixture();

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.internships.index'))
        ->assertOk()
        ->assertSee('data-ajax-application', false)
        ->assertSee('Choose a resume');
});

it('does not allow a non-student to submit an application', function () {
    $fixture = studentApplicationFixture();

    $this->actingAs($fixture['employerUser'])
        ->postJson(route('student.internships.apply', $fixture['internship']), [
            'resume_id' => $fixture['resume']->id,
        ])
        ->assertForbidden();
});

it('renders the application details for every status with its stepper, next step and actions', function (ApplicationStatus $status, array $expected, array $hidden) {
    $fixture = studentApplicationFixture();
    $application = InternshipApplication::create([
        'internship_id' => $fixture['internship']->id,
        'student_id' => $fixture['student']->id,
        'resume_id' => $fixture['resume']->id,
        'status' => $status,
        'applied_at' => now()->subDays(5),
        'interview_at' => $status === ApplicationStatus::Interview ? now()->addDays(3)->setTime(10, 0) : null,
        'cover_letter' => 'I would love to join.',
    ]);
    if ($status !== ApplicationStatus::Applied) {
        $application->statusHistory()->create(['actor_id' => $fixture['employerUser']->id, 'status' => 'reviewed', 'notes' => 'Looks promising.']);
    }
    if ($status !== ApplicationStatus::Applied && $status !== ApplicationStatus::Reviewed) {
        $application->statusHistory()->create([
            'actor_id' => $fixture['employerUser']->id,
            'status' => $status->value,
            'notes' => $status === ApplicationStatus::Rejected ? 'We chose a candidate with more experience.' : 'Bring a valid ID.',
            'interview_at' => $application->interview_at,
        ]);
    }

    $response = $this->actingAs($fixture['studentUser'])->get(route('student.applications.show', $application))->assertOk()
        ->assertSee('Application #'.$application->id)
        ->assertSee('Application submitted')
        ->assertSee('I would love to join.');
    foreach ($expected as $text) {
        $response->assertSee($text);
    }
    foreach ($hidden as $text) {
        $response->assertDontSee($text);
    }
})->with([
    'applied' => [ApplicationStatus::Applied, ['Waiting for employer review', 'Edit application', 'Withdraw application', 'Status updates from the employer will appear here.'], ['Interview schedule']],
    'reviewed' => [ApplicationStatus::Reviewed, ['Reviewed by the employer', 'Looks promising.'], ['Withdraw application', 'Interview schedule']],
    'interview' => [ApplicationStatus::Interview, ['Interview schedule', 'See interview details', 'Bring a valid ID.'], ['Withdraw application']],
    'accepted' => [ApplicationStatus::Accepted, ['You were accepted', 'Open my OJT record', 'Skipped'], ['Withdraw application']],
    'rejected' => [ApplicationStatus::Rejected, ['Not selected', 'We chose a candidate with more experience.'], ['Withdraw application']],
]);

it('orders My Applications by status priority and supports tabs, sorting and upcoming interviews', function () {
    $fixture = studentApplicationFixture();
    $make = function (string $title, ApplicationStatus $status, int $match, int $daysAgo, ?\Carbon\Carbon $interviewAt = null) use ($fixture) {
        $internship = Internship::create(['employer_id' => $fixture['employer']->id, 'title' => $title, 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);

        return InternshipApplication::create([
            'internship_id' => $internship->id,
            'student_id' => $fixture['student']->id,
            'resume_id' => $fixture['resume']->id,
            'status' => $status,
            'match_percentage' => $match,
            'applied_at' => now()->subDays($daysAgo),
            'interview_at' => $interviewAt,
        ]);
    };
    $make('Rejected Role', ApplicationStatus::Rejected, 90, 1);
    $make('Applied Role', ApplicationStatus::Applied, 80, 2);
    $make('Reviewed Role', ApplicationStatus::Reviewed, 10, 3);
    $make('Interview Role', ApplicationStatus::Interview, 20, 4, now()->addDays(2));
    $make('Accepted Role', ApplicationStatus::Accepted, 30, 5);

    $this->actingAs($fixture['studentUser']);

    // Default: Accepted > Interview > Reviewed > Applied > Rejected.
    $this->get(route('student.applications.index'))->assertOk()
        ->assertSeeInOrder(['Accepted Role', 'Interview Role', 'Reviewed Role', 'Applied Role', 'Rejected Role'])
        ->assertSee('Sorted by status')
        ->assertSee('Upcoming interviews')
        ->assertSee('data-count-up="5"', false); // total applications tile

    // Sort by match, highest first.
    $this->get(route('student.applications.index', ['sort' => 'match']))->assertOk()
        ->assertSeeInOrder(['Rejected Role', 'Applied Role', 'Accepted Role', 'Interview Role', 'Reviewed Role']);

    // A status tab shows only that status; tab counts still cover every status.
    $this->get(route('student.applications.index', ['status' => 'interview']))->assertOk()
        ->assertSee('data-total="1"', false)
        ->assertSee('Interview Role')
        ->assertDontSee('Reviewed Role');
});
