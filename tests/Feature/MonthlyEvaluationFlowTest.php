<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Appraisal;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Services\AppraisalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/** A placement with its student, supervisor and managing coordinator. */
function mePlacement(string $start = '2026-02-02', ?string $end = null, string $status = Placement::STATUS_ACTIVE, ?Institution $institution = null): array
{
    $institution ??= Institution::create(['name' => 'Eval University '.uniqid()]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer, 'name' => 'Ian Supervisor']);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Applus Labs '.uniqid(), 'contact_person' => 'Ian Supervisor']);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Joshua Trainee']);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program' => 'BSIT']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    assignDepartment(Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]), $student);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Software Developer Trainee', 'description' => 'Build.', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id,
        'internship_id' => $internship->id, 'start_date' => $start, 'end_date' => $end, 'required_hours' => 500, 'status' => $status,
        'department' => 'IT Department', // the printed Designation
    ]);

    return compact('placement', 'studentUser', 'employerUser', 'coordinatorUser', 'institution');
}

/** Full marks per factor, or a fixed value capped at each factor's points. */
function meScores(?int $each = null): array
{
    return collect(Appraisal::FACTORS)->map(fn ($factor) => $each === null ? $factor['points'] : min($each, $factor['points']))->all();
}

function meRequest(array $f, string $month = '2026-02', array $extra = []): Appraisal
{
    test()->actingAs($f['coordinatorUser'])
        ->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => $month, 'due_on' => now()->addWeek()->toDateString(), ...$extra])
        ->assertSessionHasNoErrors();

    return Appraisal::where('placement_id', $f['placement']->id)->latest('id')->firstOrFail();
}

beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00:00')));

it('runs student -> supervisor -> student comment -> adviser, blocking every step out of turn', function () {
    $f = mePlacement();
    $appraisal = meRequest($f);
    expect($appraisal->period_label)->toBe('Midterm')
        ->and($appraisal->period_start->toDateString())->toBe('2026-02-02') // clipped to the OJT start
        ->and($appraisal->period_end->toDateString())->toBe('2026-02-28')
        ->and($appraisal->stage())->toBe(Appraisal::STAGE_AWAITING_STUDENT);

    // Supervisor and coordinator cannot go first.
    $this->actingAs($f['employerUser'])->post(route('employer.interns.appraisals.rate', $appraisal), ['scores' => meScores(), 'supervisor_comment' => 'Good.'])
        ->assertSessionHas('error');
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.appraisals.adviser', $appraisal), ['scores' => meScores()])->assertSessionHas('error');
    expect($appraisal->fresh()->supervisor_submitted_at)->toBeNull();

    // 1. Student rates.
    $this->actingAs($f['studentUser'])->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => meScores(9)])->assertSessionHas('success');
    expect($appraisal->fresh()->self_total)->toBe(63) // 9 on each of the 7 factors
        ->and($appraisal->fresh()->stage())->toBe(Appraisal::STAGE_AWAITING_SUPERVISOR);
    // The student comment is not open yet, and the rating is locked.
    $this->post(route('student.ojt.appraisals.comment', $appraisal), ['student_comment' => 'Too early'])->assertSessionHas('error');
    $this->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => meScores()])->assertSessionHas('error');
    expect($appraisal->fresh()->self_total)->toBe(63);

    // 2. Supervisor rates; the comment is required.
    $this->actingAs($f['employerUser'])->post(route('employer.interns.appraisals.rate', $appraisal), ['scores' => meScores()])
        ->assertSessionHasErrorsIn('appraisal'.$appraisal->id, ['supervisor_comment']);
    $this->post(route('employer.interns.appraisals.rate', $appraisal), ['scores' => meScores(), 'supervisor_comment' => 'Strong work on DevOps tasks.'])->assertSessionHas('success');
    $appraisal->refresh();
    expect($appraisal->supervisor_total)->toBe(100)
        ->and($appraisal->supervisor_user_id)->toBe($f['employerUser']->id)
        ->and($appraisal->stage())->toBe(Appraisal::STAGE_AWAITING_STUDENT_COMMENT);

    // Coordinator still waits for the student's comment.
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.appraisals.adviser', $appraisal), ['scores' => meScores()])->assertSessionHas('error');

    // 3. Student comments.
    $this->actingAs($f['studentUser'])->post(route('student.ojt.appraisals.comment', $appraisal), ['student_comment' => 'I learned a lot about deployment.'])->assertSessionHas('success');

    // 4. Coordinator rates last.
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.appraisals.adviser', $appraisal), ['scores' => meScores(8)])->assertSessionHas('success');
    expect($appraisal->fresh()->isComplete())->toBeTrue()
        ->and($appraisal->fresh()->adviser_total)->toBe(56);
});

it('only accepts whole numbers from 0 to each factor\'s points', function () {
    $f = mePlacement();
    $appraisal = meRequest($f);
    $this->actingAs($f['studentUser']);

    foreach ([['technical_competence', 31], ['quality_of_work', -1], ['initiative', '2.5'], ['personality', 'abc']] as [$key, $bad]) {
        $this->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => [...meScores(), $key => $bad]])
            ->assertSessionHasErrorsIn('appraisal'.$appraisal->id, ['scores.'.$key]);
    }
    $this->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => collect(meScores())->except('punctuality_attendance')->all()])
        ->assertSessionHasErrorsIn('appraisal'.$appraisal->id, ['scores.punctuality_attendance']);
    expect($appraisal->fresh()->self_submitted_at)->toBeNull();

    // The service guards too, in case a caller skips request validation.
    expect(fn () => AppraisalService::cleanScores([...meScores(), 'technical_competence' => 99]))->toThrow(ValidationException::class);
});

it('lets the coordinator reopen only the latest submitted step, one at a time', function () {
    $f = mePlacement();
    $appraisal = meRequest($f);
    $service = app(AppraisalService::class);

    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.appraisals.reopen', $appraisal))->assertSessionHas('error'); // nothing submitted yet

    $service->submitSelf($appraisal, $f['studentUser'], meScores());
    $service->submitSupervisor($appraisal, $f['employerUser'], meScores(), 'Fine.');
    $service->submitStudentComment($appraisal, $f['studentUser'], 'Thanks.');
    $service->submitAdviser($appraisal, $f['coordinatorUser'], meScores());

    $this->post(route('coordinator.appraisals.reopen', $appraisal))->assertSessionHas('success', 'Reopened the coordinator rating.');
    $appraisal->refresh();
    expect($appraisal->adviser_submitted_at)->toBeNull()
        ->and($appraisal->student_comment)->toBe('Thanks.')
        ->and($appraisal->stage())->toBe(Appraisal::STAGE_AWAITING_ADVISER);

    $this->post(route('coordinator.appraisals.reopen', $appraisal))->assertSessionHas('success', 'Reopened the student comment.');
    $appraisal->refresh();
    expect($appraisal->student_comment)->toBeNull()
        ->and($appraisal->supervisor_total)->toBe(100) // earlier steps untouched
        ->and($appraisal->stage())->toBe(Appraisal::STAGE_AWAITING_STUDENT_COMMENT);
});

it('rejects a duplicate month, months outside the OJT, and placements that are not active', function () {
    $f = mePlacement('2026-02-02');
    meRequest($f, '2026-02');
    $due = now()->addWeek()->toDateString();
    $this->actingAs($f['coordinatorUser']);

    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-02', 'due_on' => $due])->assertSessionHasErrors(['period_month']);
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-01', 'due_on' => $due])->assertSessionHasErrors(['period_month']); // before start
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-04', 'due_on' => $due])->assertSessionHasErrors(['period_month']); // future month
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-03', 'due_on' => now()->subDay()->toDateString()])->assertSessionHasErrors(['due_on']);

    foreach ([Placement::STATUS_PENDING_VALIDATION, Placement::STATUS_COMPLETED] as $status) {
        $other = mePlacement('2026-02-02', null, $status);
        $this->actingAs($other['coordinatorUser'])
            ->post(route('coordinator.placements.appraisals.store', $other['placement']), ['period_month' => '2026-02', 'due_on' => $due])
            ->assertSessionHasErrors(['period_month' => 'Monthly evaluations can only be requested while the OJT is active.']);
    }
    expect(Appraisal::count())->toBe(1);
});

it('clips the period to the OJT start and end and keeps custom dates inside it', function () {
    $this->travelTo(CarbonImmutable::parse('2026-05-05 09:00:00'));
    $f = mePlacement('2026-01-28', '2026-04-24');

    $january = meRequest($f, '2026-01');
    $april = meRequest($f, '2026-04');
    expect([$january->period_start->toDateString(), $january->period_end->toDateString(), $january->period_label])->toBe(['2026-01-28', '2026-01-31', 'Prelim'])
        ->and([$april->period_start->toDateString(), $april->period_end->toDateString(), $april->period_label])->toBe(['2026-04-01', '2026-04-24', 'Finals'])
        ->and($april->inclusiveDates())->toBe('April 1 - 24, 2026');

    // May is after the OJT ended.
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-05', 'due_on' => now()->addWeek()->toDateString()])->assertSessionHasErrors(['period_month']);
    // Custom dates must stay inside the clipped month.
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-03', 'due_on' => now()->addWeek()->toDateString(), 'period_start' => '2026-02-25', 'period_end' => '2026-03-31'])
        ->assertSessionHasErrors(['period_start']);
    $march = meRequest($f, '2026-03', ['period_start' => '2026-03-02', 'period_end' => '2026-03-27']);
    expect([$march->period_start->toDateString(), $march->period_end->toDateString(), $march->period_label])->toBe(['2026-03-02', '2026-03-27', 'Prefinals']);
});

it('uses the configured month names, falling back to the month name', function () {
    AcademicSetting::current()->update(['evaluation_period_labels' => [1 => 'Prelim', 2 => 'Second Quarter', 3 => '']]);
    $f = mePlacement('2026-02-02');

    expect(meRequest($f, '2026-02')->period_label)->toBe('Second Quarter')
        ->and(meRequest($f, '2026-03')->period_label)->toBe('March')
        ->and(AcademicSetting::current()->evaluationPeriodLabelFor(6))->toBe('June');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.academics.index'))->assertOk()
        ->assertSee('Monthly evaluation periods')->assertSee('value="Second Quarter"', false);
});

it('bulk-requests a month for every active placement the coordinator manages, skipping the rest', function () {
    $institution = Institution::create(['name' => 'Bulk University']);
    $a = mePlacement('2026-02-02', null, Placement::STATUS_ACTIVE, $institution);
    $b = mePlacement('2026-02-10', null, Placement::STATUS_ACTIVE, $institution);
    $late = mePlacement('2026-03-02', null, Placement::STATUS_ACTIVE, $institution); // starts after February
    $pending = mePlacement('2026-02-02', null, Placement::STATUS_PENDING_VALIDATION, $institution);
    $elsewhere = mePlacement('2026-02-02'); // another institution
    meRequest($b, '2026-02'); // already requested
    // The same coordinator handles a, b, late and pending: put those students in a's department.
    foreach ([$b, $late, $pending] as $other) {
        $other['placement']->student->update(['program_id' => $a['placement']->student->program_id]);
    }

    $this->actingAs($a['coordinatorUser'])
        ->post(route('coordinator.appraisals.bulk'), ['period_month' => '2026-02', 'due_on' => now()->addWeek()->toDateString()])
        ->assertSessionHas('success', 'Monthly evaluation for February 2026 requested for 1 placement(s). Skipped 2 (already requested, or the month is outside the OJT dates).');

    expect($a['placement']->appraisals()->count())->toBe(1)
        ->and($b['placement']->appraisals()->count())->toBe(1)
        ->and($late['placement']->appraisals()->count())->toBe(0)
        ->and($pending['placement']->appraisals()->count())->toBe(0)
        ->and($elsewhere['placement']->appraisals()->count())->toBe(0);
});

it('lets only the right people act on or print an evaluation', function () {
    $f = mePlacement();
    $appraisal = meRequest($f);
    $other = mePlacement(); // another student, company and institution

    // Another company's supervisor, another student, another institution's coordinator.
    $this->actingAs($other['studentUser'])->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => meScores()])->assertForbidden();
    $this->actingAs($other['employerUser'])->post(route('employer.interns.appraisals.rate', $appraisal), ['scores' => meScores(), 'supervisor_comment' => 'x'])->assertForbidden();
    $this->actingAs($other['coordinatorUser'])->post(route('coordinator.appraisals.adviser', $appraisal), ['scores' => meScores()])->assertForbidden();
    $this->post(route('coordinator.appraisals.reopen', $appraisal))->assertForbidden();
    $this->post(route('coordinator.placements.appraisals.store', $f['placement']), ['period_month' => '2026-03', 'due_on' => now()->addWeek()->toDateString()])->assertForbidden();

    // Print: own student, own company's supervisor, managing coordinator, admin.
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    foreach ([$f['studentUser'], $f['employerUser'], $f['coordinatorUser'], $admin] as $allowed) {
        $this->actingAs($allowed)->get(route('ojt.appraisals.print', $appraisal))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
    foreach ([$other['studentUser'], $other['employerUser'], $other['coordinatorUser']] as $denied) {
        $this->actingAs($denied)->get(route('ojt.appraisals.print', $appraisal))->assertForbidden();
    }
    expect($appraisal->fresh()->self_submitted_at)->toBeNull();
});

it('shows the Monthly Evaluation on each role\'s OJT page with the form only on that role\'s turn', function () {
    $f = mePlacement();
    $appraisal = meRequest($f);

    $this->actingAs($f['coordinatorUser'])->get(route('coordinator.placements.show', $f['placement']))->assertOk()
        ->assertSee('Request a monthly evaluation')->assertSee('Monthly Evaluation: Midterm · February 2026')
        ->assertSee('<option value="2026-03"', false)->assertDontSee('<option value="2026-02"', false);
    $this->actingAs($f['studentUser'])->get(route('student.ojt.index', ['tab' => 'monthly-evaluation']))->assertOk()
        ->assertSee('Monthly Evaluation')->assertSee(route('student.ojt.appraisals.self', $appraisal), false);
    $this->actingAs($f['employerUser'])->get(route('employer.interns.show', $f['placement']))->assertOk()
        ->assertSee('Final Evaluation')->assertSee('Waiting for the student')
        ->assertDontSee(route('employer.interns.appraisals.rate', $appraisal), false);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.placements.show', $f['placement']))->assertOk()
        ->assertSee('Monthly Evaluation: Midterm')->assertDontSee('Request a monthly evaluation');
    $this->actingAs($f['coordinatorUser'])->get(route('coordinator.placements.index'))->assertOk()
        ->assertSee('Request a monthly evaluation for all ongoing OJTs');
});

it('saves the month names from the Academic settings form', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payload = AcademicSetting::current()->toArray();
    $payload['evaluation_period_labels'] = [1 => 'Prelim', 2 => 'Midterm', 3 => 'Prefinals', 4 => 'Finals', 6 => 'Summer Term'];

    $this->actingAs($admin)->post(route('admin.academics.settings.update'), $payload)
        ->assertRedirect(route('admin.academics.index'))->assertSessionHasNoErrors();
    expect(AcademicSetting::current()->evaluationPeriodLabelFor(6))->toBe('Summer Term')
        ->and(AcademicSetting::current()->evaluationPeriodLabelFor(7))->toBe('July');

    $payload['evaluation_period_labels'] = [13 => 'Bad month'];
    $this->post(route('admin.academics.settings.update'), $payload)->assertSessionHasErrors('evaluation_period_labels');
});
