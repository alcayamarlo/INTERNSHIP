<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\AttendanceLog;
use App\Models\Coordinator;
use App\Models\DeploymentRequirement;
use App\Models\EligibilityDecision;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\EligibilityChecker;
use App\Services\EligibilityCriteria;
use App\Services\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A coordinator with one program, a student in it, and a verified company with an open posting. */
function workflow(array $studentAttributes = []): array
{
    $institution = Institution::create(['name' => 'SCC']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $program = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'coordinator_id' => $coordinator->id, 'required_internship_hours' => 486]);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz']);
    $student = Student::create([
        'user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program_id' => $program->id, 'program' => 'BSIT',
        'year_level' => '4th Year', 'major_subjects_passed' => true, 'privacy_consent' => true, 'privacy_consent_at' => now(),
        ...$studentAttributes,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme Corp', 'contact_person' => 'Grace HR', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);

    return compact('institution', 'coordinatorUser', 'coordinator', 'program', 'studentUser', 'student', 'employerUser', 'employer', 'internship');
}

function workflowPlacement(array $w, string $status = Placement::STATUS_ACTIVE): Placement
{
    $application = InternshipApplication::create(['internship_id' => $w['internship']->id, 'student_id' => $w['student']->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);
    $placement = app(PlacementService::class)->openForApplication($application);
    if ($status !== Placement::STATUS_PENDING_VALIDATION) {
        $placement->update(['status' => $status, 'start_date' => now()->subDays(20)->toDateString()]);
    }

    return $placement->fresh();
}

// Step 3
it('shows a pass/fail line for every rule, including required documents', function () {
    $w = workflow(['gwa' => 2.0]);
    $cor = DeploymentRequirement::create(['code' => 'wf-cor', 'name' => 'Certificate of registration (test)', 'is_active' => true, 'on_request' => false, 'upload_source' => DeploymentRequirement::SOURCE_STUDENT, 'sort_order' => 1]);
    app(EligibilityCriteria::class)->saveVersion($w['program'], ...array_values(app(EligibilityCriteria::class)->currentTerm()), ...[[
        'require_fourth_year' => true, 'require_major_subjects_passed' => true, 'max_gwa' => 2.5, 'required_hours' => null, 'required_document_ids' => [$cor->id],
    ], $w['coordinatorUser']]);

    $check = app(EligibilityChecker::class)->check($w['student']);
    expect(collect($check['rules'])->pluck('passed', 'key')->all())->toBe(['fourth_year' => true, 'major_subjects' => true, 'gwa' => true, 'documents' => false])
        ->and($check['eligible'])->toBeFalse();

    Portfolio::create(['student_id' => $w['student']->id, 'title' => 'COR', 'type' => 'transcript', 'deployment_requirement_id' => $cor->id, 'file_path' => 'docs/cor.pdf', 'verification_status' => 'submitted']);
    expect(app(EligibilityChecker::class)->check($w['student']->fresh())['eligible'])->toBeTrue();

    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.students.show', $w['student']))
        ->assertOk()->assertSee('Internship eligibility')->assertSee('Eligible to apply');
});

// Step 5
it('lets the coordinator reject with a required reason, notifies the student, keeps history, and approval clears it', function () {
    $w = workflow();
    $this->actingAs($w['coordinatorUser']);

    $this->post(route('coordinator.students.eligibility.store', $w['student']), ['decision' => 'rejected'])
        ->assertSessionHasErrors(['reason' => 'Give a reason for the rejection. The student will see it.']);
    $this->post(route('coordinator.students.eligibility.store', $w['student']), ['decision' => 'rejected', 'reason' => 'Incomplete clearance'])->assertRedirect();

    expect(app(EligibilityChecker::class)->reasons($w['student']->fresh()))->toBe(['Your coordinator marked you not eligible: Incomplete clearance'])
        ->and(AppNotification::where('user_id', $w['studentUser']->id)->where('type', 'eligibility_decision')->exists())->toBeTrue();
    $this->actingAs($w['studentUser'])->postJson(route('student.internships.apply', $w['internship']), ['resume_id' => 1])->assertUnprocessable();

    $this->actingAs($w['coordinatorUser'])->post(route('coordinator.students.eligibility.store', $w['student']), ['decision' => 'approved'])->assertRedirect();
    expect(app(EligibilityChecker::class)->reasons($w['student']->fresh()))->toBe([])
        ->and(EligibilityDecision::where('student_id', $w['student']->id)->count())->toBe(2);
    $this->get(route('coordinator.students.show', $w['student']))->assertSee('Decision history')->assertSee('Incomplete clearance');
    $this->assertDatabaseHas('system_logs', ['action' => 'eligibility_rejected']);
});

it('does not let a coordinator decide for a student outside their scope', function () {
    $w = workflow();
    $outsider = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $outsider->id, 'institution_id' => Institution::create(['name' => 'Other'])->id]);

    $this->actingAs($outsider)->post(route('coordinator.students.eligibility.store', $w['student']), ['decision' => 'rejected', 'reason' => 'x'])->assertForbidden();
    expect(EligibilityDecision::count())->toBe(0);
});

// Step 6
it('shows application stages and unplaced students', function () {
    $w = workflow();
    InternshipApplication::create(['internship_id' => $w['internship']->id, 'student_id' => $w['student']->id, 'status' => ApplicationStatus::Interview, 'match_percentage' => 0, 'applied_at' => now()]);

    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.applications.index'))
        ->assertOk()
        ->assertSee('Web Intern')
        ->assertSee('Students not placed yet (1)')
        ->assertSee('data-unplaced="'.$w['student']->id.'"', false);
    $this->get(route('coordinator.applications.index', ['stage' => 'placed']))->assertOk()->assertSee('No applications match.');
});

// Step 7
it('makes new placements wait for validation, then validates them', function () {
    $w = workflow();
    $placement = workflowPlacement($w, Placement::STATUS_PENDING_VALIDATION);
    expect($placement->isPendingValidation())->toBeTrue();

    // Students cannot log hours yet.
    $this->actingAs($w['studentUser'])->post(route('student.ojt.attendance.store', $placement), ['work_date' => now()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60])->assertForbidden();

    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.placements.show', $placement))
        ->assertOk()->assertSee('Validate before deployment')->assertSee('No MOA on file');
    // The missing MOA is a warning that must be acknowledged.
    $this->post(route('coordinator.placements.validate', $placement))->assertSessionHas('error');
    expect($placement->fresh()->isPendingValidation())->toBeTrue();
    $this->post(route('coordinator.placements.hold', $placement), ['validation_remarks' => 'Send the signed MOA'])->assertRedirect();
    expect($placement->fresh()->validation_remarks)->toBe('Send the signed MOA');

    $this->post(route('coordinator.placements.validate', $placement), ['acknowledge_warnings' => '1'])->assertRedirect();
    expect($placement->fresh()->isActive())->toBeTrue()
        ->and($placement->fresh()->validated_by_user_id)->toBe($w['coordinatorUser']->id)
        ->and(AppNotification::where('user_id', $w['studentUser']->id)->where('title', 'Placement validated')->exists())->toBeTrue();
});

it('refuses to validate a placement with an unverified company', function () {
    $w = workflow();
    $w['employer']->update(['verified_at' => null]);
    $placement = workflowPlacement($w, Placement::STATUS_PENDING_VALIDATION);

    $this->actingAs($w['coordinatorUser'])->post(route('coordinator.placements.validate', $placement), ['acknowledge_warnings' => '1'])
        ->assertSessionHas('error', 'This company is not verified yet, so the placement cannot be validated.');
    expect($placement->fresh()->isPendingValidation())->toBeTrue();
});

it('hides a posting the coordinator marked not suitable from their students', function () {
    $w = workflow();
    $this->actingAs($w['coordinatorUser'])->post(route('coordinator.internships.flag', $w['internship']), ['reason' => 'Unrelated to IT'])->assertRedirect();

    $this->actingAs($w['studentUser'])->get(route('student.internships.index', ['scope' => 'all']))->assertOk()->assertDontSee('Web Intern');
    expect(fn () => app(\App\Services\ApplicationService::class)->ensureCanApply($w['student'], $w['internship']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    $this->actingAs($w['coordinatorUser'])->delete(route('coordinator.internships.unflag', $w['internship']))->assertRedirect();
    $this->actingAs($w['studentUser'])->get(route('student.internships.index', ['scope' => 'all']))->assertSee('Web Intern');
});

// Step 8
it('lists partner companies with MOA status and reminds coordinators before an MOA expires', function () {
    $w = workflow();
    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.companies.index'))->assertOk()->assertSee('Acme Corp')->assertSee('No MOA on file');

    $this->put(route('coordinator.companies.moa.update', $w['employer']), ['moa_signed_on' => now()->subYear()->toDateString(), 'moa_expires_on' => now()->addDays(10)->toDateString()])->assertRedirect();
    $this->get(route('coordinator.companies.show', $w['employer']))->assertOk()->assertSee('MOA expiring soon')->assertSee('Grace HR')->assertSee('Communication log');

    $this->artisan('moa:remind')->assertSuccessful();
    $this->artisan('moa:remind')->assertSuccessful();
    expect(AppNotification::where('user_id', $w['coordinatorUser']->id)->where('type', 'moa_expiring')->count())->toBe(1);

    $this->put(route('coordinator.companies.moa.update', $w['employer']), ['moa_signed_on' => now()->toDateString(), 'moa_expires_on' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors('moa_expires_on');
});

// Step 9
it('flags active OJTs with no recent time as at risk', function () {
    $w = workflow();
    $placement = workflowPlacement($w);

    expect(Placement::query()->atRisk()->pluck('id')->all())->toBe([$placement->id])
        ->and($placement->riskReason())->toBe('No time logged in the last 7 days');
    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.placements.index', ['status' => 'at_risk']))->assertOk()->assertSee('At risk')->assertSee('Ana Cruz');

    AttendanceLog::create(['placement_id' => $placement->id, 'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'hours' => 8, 'task_summary' => 'Coding', 'status' => AttendanceLog::STATUS_PENDING]);
    expect(Placement::query()->atRisk()->count())->toBe(0)->and($placement->fresh()->riskReason())->toBeNull();
});

// Step 10
it('lets the coordinator review or return weekly reports and evaluations after the supervisor', function () {
    $w = workflow();
    $placement = workflowPlacement($w);
    $journal = JournalEntry::create(['placement_id' => $placement->id, 'week_start' => now()->startOfWeek()->toDateString(), 'title' => 'Week 1', 'accomplishments' => 'Built forms', 'status' => JournalEntry::STATUS_ACKNOWLEDGED]);
    $evaluation = Evaluation::create(['placement_id' => $placement->id, 'evaluator_user_id' => $w['employerUser']->id, 'scores' => ['quality' => 4], 'average_score' => 4, 'submitted_at' => now()]);
    $this->actingAs($w['coordinatorUser']);

    $this->get(route('coordinator.reviews.index'))->assertOk()->assertSee('Week 1');
    $this->post(route('coordinator.reviews.journals', $journal), ['decision' => 'returned'])->assertSessionHasErrors('comment');
    $this->post(route('coordinator.reviews.journals', $journal), ['decision' => 'returned', 'comment' => 'Add your daily tasks'])->assertRedirect();
    expect($journal->fresh()->isReturnedByCoordinator())->toBeTrue()
        ->and(AppNotification::where('user_id', $w['studentUser']->id)->where('title', 'Weekly report returned')->exists())->toBeTrue();

    // The student may replace a returned report even though the supervisor acknowledged it.
    $this->actingAs($w['studentUser'])->delete(route('student.ojt.journals.destroy', [$placement, $journal]))->assertSessionHas('success');

    $this->actingAs($w['coordinatorUser'])->get(route('coordinator.reviews.index', ['tab' => 'evaluations']))->assertOk()->assertSee('Average 4.00 / 5');
    $this->post(route('coordinator.reviews.evaluations', $evaluation), ['decision' => 'reviewed'])->assertRedirect();
    expect($evaluation->fresh()->coordinator_review_status)->toBe('reviewed');
    $this->get(route('coordinator.reviews.index', ['tab' => 'evaluations']))->assertSee('Nothing waiting for your review.');
});

// Step 11
it('shows institutional statistics and exports them as PDF and Excel', function () {
    $w = workflow();
    workflowPlacement($w);
    $this->actingAs($w['coordinatorUser']);

    $this->get(route('coordinator.statistics.index'))
        ->assertOk()
        ->assertSee('data-total="enrolled">1<', false)
        ->assertSee('data-total="placed">1<', false)
        ->assertSee('data-rate="placement">100%<', false)
        ->assertSee('Acme Corp');

    $pdf = $this->get(route('coordinator.statistics.export', 'pdf'));
    $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $xlsx = $this->get(route('coordinator.statistics.export', 'xlsx'));
    $xlsx->assertOk()->assertDownload();
    expect(file_get_contents($xlsx->baseResponse->getFile()->getPathname()))->toStartWith('PK');
});

// Step 1
it('filters students by year level and placement status, and records GWA', function () {
    $w = workflow();
    $other = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student, 'name' => 'Ben Reyes'])->id, 'institution_id' => $w['institution']->id, 'program_id' => $w['program']->id, 'program' => 'BSIT', 'year_level' => '3rd Year']);
    workflowPlacement($w);
    $this->actingAs($w['coordinatorUser']);

    $placed = $this->getJson(route('coordinator.students.index', ['status' => 'placed']))->assertOk()->json('students');
    $thirdYear = $this->getJson(route('coordinator.students.index', ['year_level' => '3rd Year']))->assertOk()->json('students');
    expect(collect($placed)->pluck('id')->all())->toBe([$w['student']->id])
        ->and(collect($thirdYear)->pluck('id')->all())->toBe([$other->id]);

    $this->put(route('coordinator.students.academic-readiness.update', $w['student']), ['rendered_internship_hours' => 0, 'major_subjects_passed' => '1', 'gwa' => '1.75'])->assertRedirect();
    expect((float) $w['student']->fresh()->gwa)->toBe(1.75);
});
