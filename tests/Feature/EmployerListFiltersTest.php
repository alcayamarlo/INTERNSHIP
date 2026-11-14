<?php

use App\Enums\ApplicationStatus;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function filterEmployer(string $company = 'Filter Co'): Employer
{
    $user = User::factory()->create(['role' => UserRole::Employer]);

    return Employer::create(['user_id' => $user->id, 'company_name' => $company, 'contact_person' => 'HR', 'verified_at' => now()]);
}

function filterInternship(Employer $employer, string $title): Internship
{
    return Internship::create(['employer_id' => $employer->id, 'title' => $title, 'description' => 'Work.', 'work_setup' => 'onsite', 'status' => 'open']);
}

function filterStudent(string $name, string $program): Student
{
    return Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student, 'name' => $name])->id, 'program' => $program]);
}

function filterApply(Internship $internship, Student $student, ApplicationStatus $status): InternshipApplication
{
    return InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => $status, 'match_percentage' => 60, 'applied_at' => now()]);
}

it('filters applicants by name, internship, status group and program, combining them', function () {
    $employer = filterEmployer();
    $web = filterInternship($employer, 'Web Developer Intern');
    $design = filterInternship($employer, 'Graphic Designer');
    filterApply($web, filterStudent('Ana Reyes', 'BSIT'), ApplicationStatus::Applied);
    filterApply($web, filterStudent('Ben Cruz', 'BSBA'), ApplicationStatus::Reviewed);
    filterApply($design, filterStudent('Carla Diaz', 'BSIT'), ApplicationStatus::Rejected);

    // Another company's applicant never shows up, not even as a Program option.
    filterApply(filterInternship(filterEmployer('Other Co'), 'Elsewhere'), filterStudent('Zed Other', 'BSCS'), ApplicationStatus::Applied);

    $this->actingAs($employer->user);
    $index = route('employer.applicants.index');

    $this->get($index)->assertOk()
        ->assertSee('data-table-filters', false)
        ->assertSee('Showing 3 of 3')
        ->assertSee('<option value="BSIT"', false)->assertSee('<option value="BSBA"', false)->assertDontSee('<option value="BSCS"', false)
        ->assertSee('All Programs')
        ->assertSeeInOrder(['Pending', 'Under Review', 'Accepted', 'Rejected'])
        ->assertDontSee('Zed Other');

    // Name: partial and case-insensitive.
    $this->get($index.'?q=ANA')->assertSee('Ana Reyes')->assertDontSee('Ben Cruz')->assertSee('Showing 1 of 3');
    // AND: BSIT + Pending is only Ana (Carla is BSIT but Rejected).
    $this->get($index.'?program=BSIT&status=pending')->assertSee('Ana Reyes')->assertDontSee('Carla Diaz')->assertDontSee('Ben Cruz');
    // "Under Review" covers the stored "reviewed" status.
    $this->get($index.'?status=under_review')->assertSee('Ben Cruz')->assertDontSee('Ana Reyes');
    $this->get($index.'?internship='.$design->id)->assertSee('Carla Diaz')->assertDontSee('Ana Reyes');
    // Nothing matches: empty state and a zero count.
    $this->get($index.'?q=nobody')->assertSee('No results found')->assertSee('Showing 0 of 3');
    // Unknown values are ignored instead of failing.
    $this->get($index.'?status=bogus&internship=abc')->assertOk()->assertSee('Showing 3 of 3');
});

it('keeps the filters on pagination links and starts filtered results on page 1', function () {
    $employer = filterEmployer();
    $web = filterInternship($employer, 'Web Developer Intern');
    foreach (range(1, 12) as $i) {
        filterApply($web, filterStudent("BSIT Student {$i}", 'BSIT'), ApplicationStatus::Applied);
    }
    filterApply($web, filterStudent('Business Student', 'BSBA'), ApplicationStatus::Applied);

    $response = $this->actingAs($employer->user)->get(route('employer.applicants.index', ['program' => 'BSIT']))->assertOk();
    $applications = $response->viewData('applications');

    expect($applications->total())->toBe(12)
        ->and($applications->currentPage())->toBe(1)
        ->and($applications->url(2))->toContain('program=BSIT');
    $response->assertSee('Showing 12 of 13');
});

it('filters sent invitations by name, internship, status and program', function () {
    $employer = filterEmployer();
    $web = filterInternship($employer, 'Web Developer Intern');
    $design = filterInternship($employer, 'Graphic Designer');
    $invite = fn (Internship $internship, Student $student, InvitationStatus $status) => InternshipInvitation::create([
        'employer_id' => $employer->id, 'student_id' => $student->id, 'internship_id' => $internship->id,
        'invited_by_user_id' => $employer->user_id, 'status' => $status, 'expires_at' => now()->addWeek(),
    ]);
    $invite($web, filterStudent('Ana Reyes', 'BSIT'), InvitationStatus::Pending);
    $invite($web, filterStudent('Ben Cruz', 'BSBA'), InvitationStatus::Declined);
    $invite($design, filterStudent('Carla Diaz', 'BSIT'), InvitationStatus::Expired);

    $this->actingAs($employer->user);
    $index = route('employer.invitations.index');

    $this->get($index)->assertOk()->assertSee('Showing 3 of 3')->assertSeeInOrder(['Pending', 'Accepted', 'Declined', 'Expired']);
    $this->get($index.'?status=declined')->assertSee('Ben Cruz')->assertDontSee('Ana Reyes');
    $this->get($index.'?program=BSIT&internship='.$design->id)->assertSee('Carla Diaz')->assertDontSee('Ana Reyes')->assertSee('Showing 1 of 3');
    $this->get($index.'?q=reyes')->assertSee('Ana Reyes')->assertDontSee('Carla Diaz');
    $this->get($index.'?status=accepted')->assertSee('No results found');
});
