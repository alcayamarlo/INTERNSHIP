<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Improvements from the Phase 2 role walkthrough: eligibility shown before Apply, the resume page
 * warning and way back, the coordinator's own school on the dashboard, and "Pending" on the company side.
 */

function flowStudent(array $attributes = []): array
{
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id, ...$attributes]);

    return [$user, $student];
}

function flowInternship(): Internship
{
    $employer = Employer::create([
        'user_id' => User::factory()->create(['role' => UserRole::Employer])->id,
        'company_name' => 'Flow Labs',
        'contact_person' => 'Fiona Flow',
    ]);

    return Internship::create(['employer_id' => $employer->id, 'title' => 'Flow Intern', 'description' => 'Flow work.', 'status' => 'open']);
}

it('tells a student who is not eligible yet before they press Apply', function () {
    $internship = flowInternship();
    [$user, $student] = flowStudent(['year_level' => '3rd Year', 'major_subjects_passed' => false]);
    Resume::create(['student_id' => $student->id, 'content' => []]);

    $this->actingAs($user)->get(route('student.internships.index'))
        ->assertOk()
        ->assertSee('You are not yet eligible to apply.')
        ->assertSee('Your coordinator must confirm that all major subjects are passed.')
        ->assertSee('Not eligible yet')
        ->assertDontSee('data-apply-id="'.$internship->id.'"', false);
});

it('keeps the Apply button for an eligible student', function () {
    $internship = flowInternship();
    [$user, $student] = flowStudent(['year_level' => '4th Year', 'major_subjects_passed' => true]);
    Resume::create(['student_id' => $student->id, 'content' => []]);

    $this->actingAs($user)->get(route('student.internships.index'))
        ->assertOk()
        ->assertDontSee('You are not yet eligible to apply.')
        ->assertSee('data-apply-id="'.$internship->id.'"', false);
});

it('warns on the resume page when the profile is incomplete and links back to internships', function () {
    [$user] = flowStudent();

    $this->actingAs($user)->get(route('student.resume.index'))
        ->assertOk()
        ->assertSee('Your profile is not complete, so your resume will have gaps.')
        ->assertSee('Still missing: Personal, Contact, Academic, Skills, Portfolio.')
        ->assertSee(route('student.profile.edit'), false)
        ->assertSee('Back to internships')
        ->assertSee(route('student.internships.index'), false);
});

it("shows the coordinator's own school on the dashboard", function () {
    $institution = Institution::create(['name' => 'Northfield Polytechnic']);
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    assignDepartment(Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id]));

    $this->actingAs($user)->get(route('coordinator.dashboard'))
        ->assertOk()
        ->assertSee('Coordinator – Department')
        ->assertSee('Northfield Polytechnic')
        ->assertDontSee('Cecilia’s College-Cebu, Inc.', false);
});

it('labels a new application Pending for the company, matching the filter, and Applied for the student', function () {
    $internship = flowInternship();
    [$user, $student] = flowStudent(['year_level' => '4th Year', 'major_subjects_passed' => true]);
    $application = InternshipApplication::create([
        'student_id' => $student->id, 'internship_id' => $internship->id, 'status' => ApplicationStatus::Applied, 'applied_at' => now(),
    ]);

    expect(ApplicationStatus::Applied->companyLabel())->toBe('Pending')
        ->and(ApplicationStatus::Reviewed->companyLabel())->toBe('Reviewed');

    $this->actingAs($internship->employer->user)->get(route('employer.applicants.index'))
        ->assertOk()
        ->assertSee('sb-status--info">Pending</span>', false);
    $this->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSee('data-application-status>Pending</span>', false);

    $this->actingAs($user)->get(route('student.applications.index'))
        ->assertOk()
        ->assertSee('Applied');
});

it('lets the coordinator request the final evaluation only once the OJT is validated and ongoing', function () {
    $institution = Institution::create(['name' => 'Request University']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $internship = flowInternship();
    [, $student] = flowStudent(['institution_id' => $institution->id]);
    assignDepartment($coordinator, $student);
    $application = InternshipApplication::create([
        'student_id' => $student->id, 'internship_id' => $internship->id, 'status' => ApplicationStatus::Accepted, 'applied_at' => now(),
    ]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $internship->employer_id,
        'internship_id' => $internship->id, 'start_date' => now()->toDateString(), 'required_hours' => 486,
        'status' => Placement::STATUS_PENDING_VALIDATION,
    ]);

    // Waiting for validation: no form, and a direct request is refused.
    $this->actingAs($coordinatorUser)->get(route('coordinator.placements.show', $placement))
        ->assertOk()
        ->assertSee('It can be requested once the OJT is validated and ongoing.')
        ->assertDontSee('route="'.route('coordinator.placements.request-rating', $placement).'"', false)
        ->assertDontSee('action="'.route('coordinator.placements.request-rating', $placement).'"', false);
    $this->post(route('coordinator.placements.request-rating', $placement), ['rating_due_on' => now()->addWeek()->toDateString()])
        ->assertSessionHas('error', 'The final evaluation can be requested once the OJT is validated and ongoing.');
    expect($placement->fresh()->rating_requested_at)->toBeNull();

    // Ongoing: the form shows and the request goes through.
    $placement->update(['status' => Placement::STATUS_ACTIVE]);
    $this->get(route('coordinator.placements.show', $placement))
        ->assertSee('action="'.route('coordinator.placements.request-rating', $placement).'"', false);
    $this->post(route('coordinator.placements.request-rating', $placement), ['rating_due_on' => now()->addWeek()->toDateString()])
        ->assertSessionHas('success');
    expect($placement->fresh()->rating_requested_at)->not->toBeNull();
});

it('shows the weekly report sections, not the old accomplishments field, on Report Reviews', function () {
    $institution = Institution::create(['name' => 'Review University']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $internship = flowInternship();
    [, $student] = flowStudent(['institution_id' => $institution->id]);
    assignDepartment($coordinator, $student);
    $application = InternshipApplication::create([
        'student_id' => $student->id, 'internship_id' => $internship->id, 'status' => ApplicationStatus::Accepted, 'applied_at' => now(),
    ]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $internship->employer_id,
        'internship_id' => $internship->id, 'start_date' => now()->subWeeks(2)->toDateString(), 'required_hours' => 486,
        'status' => Placement::STATUS_ACTIVE,
    ]);
    JournalEntry::create([
        'placement_id' => $placement->id, 'week_start' => now()->subWeek()->startOfWeek()->toDateString(), 'title' => 'Week 1 – Activity Narrative Report',
        'challenges' => 'Learning the codebase', 'actions_taken' => 'Asked the supervisor', 'learnings' => 'Code review habits',
        'status' => JournalEntry::STATUS_ACKNOWLEDGED,
    ]);

    $this->actingAs($coordinatorUser)->get(route('coordinator.reviews.index'))
        ->assertOk()
        ->assertSee('Difficulties encountered:')
        ->assertSee('Learning the codebase')
        ->assertSee('Actions taken:')
        ->assertSee('Asked the supervisor')
        ->assertSee('Learning insights:')
        ->assertSee('Code review habits')
        ->assertDontSee('Accomplishments:');
});
