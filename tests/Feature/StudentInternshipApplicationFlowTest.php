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

it('does not offer or accept an archived resume', function () {
    $fixture = studentApplicationFixture();
    $fixture['resume']->archive();

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.internships.show', $fixture['internship']))
        ->assertOk()
        ->assertDontSee('value="'.$fixture['resume']->id.'"', false);

    $this->postJson(route('student.internships.apply', $fixture['internship']), [
        'resume_id' => $fixture['resume']->id,
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
