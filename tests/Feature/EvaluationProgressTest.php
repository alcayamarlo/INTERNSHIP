<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** One coordinator (BSIT) with a placement for one of their students, plus a student of another department. */
function epSetup(): array
{
    $institution = Institution::create(['name' => 'Progress College']);
    $bsit = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'required_internship_hours' => 486]);
    $bsba = Program::create(['name' => 'BS Business Administration', 'code' => 'BSBA', 'required_internship_hours' => 486]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id, 'program_id' => $bsit->id]);

    $mine = epPlacement($institution, $bsit, 'Ana Cruz', 'Applus Labs');
    $other = epPlacement($institution, $bsba, 'Ben Reyes', 'Other Corp');

    return compact('institution', 'coordinatorUser', 'coordinator', 'mine', 'other');
}

function epPlacement(Institution $institution, Program $program, string $studentName, string $company): array
{
    $employerUser = User::factory()->create(['role' => UserRole::Employer, 'name' => $company.' Supervisor']);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => $company, 'contact_person' => 'Supervisor']);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => $studentName]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program_id' => $program->id, 'student_id_number' => 'SCC-'.random_int(1000, 9999)]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Trainee', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id,
        'internship_id' => $internship->id, 'start_date' => '2026-02-02', 'required_hours' => 500, 'status' => Placement::STATUS_ACTIVE,
    ]);

    return compact('placement', 'studentUser', 'employerUser', 'student');
}

function epAppraisal(Placement $placement, string $month, array $attributes = []): Appraisal
{
    return Appraisal::create([
        'placement_id' => $placement->id, 'period_month' => $month.'-01', 'period_label' => 'Midterm',
        'period_start' => $month.'-01', 'period_end' => $month.'-28', 'due_on' => now()->addWeek()->toDateString(),
        ...$attributes,
    ]);
}

function epEvaluation(Placement $placement, array $attributes = []): Evaluation
{
    return Evaluation::forceCreate([
        'placement_id' => $placement->id, 'scores' => [], 'average_score' => 4.5, 'submitted_at' => now(),
        'form_version' => 2, 'work_total' => 95, 'trait_total' => 90, ...$attributes,
    ]);
}

it('lists every requested monthly evaluation of the coordinator\'s department with who did what', function () {
    $f = epSetup();
    $placement = $f['mine']['placement'];
    epAppraisal($placement, '2026-02'); // nobody has started
    epAppraisal($placement, '2026-03', ['self_scores' => [], 'self_total' => 85, 'self_submitted_at' => now()->subDays(2)]); // supervisor's turn
    epAppraisal($placement, '2026-04', [
        'self_total' => 80, 'self_submitted_at' => now()->subDays(3), 'supervisor_total' => 90, 'supervisor_submitted_at' => now()->subDay(),
        'student_comment_submitted_at' => now(), // the coordinator's turn
    ]);
    epAppraisal($f['other']['placement'], '2026-02', ['period_label' => 'OtherDepartmentLabel']);

    $page = $this->actingAs($f['coordinatorUser'])->get(route('coordinator.reviews.index', ['tab' => 'evaluations']))->assertOk();
    $page->assertSee('Ana Cruz')->assertSee('Applus Labs')
        ->assertSee('Not rated yet')->assertSee('85 / 100')->assertSee('90 / 100')
        ->assertSee('Waiting for student')->assertSee('Waiting for supervisor')->assertSee('Your turn')
        // Another department's evaluation never shows.
        ->assertDontSee('Ben Reyes')->assertDontSee('OtherDepartmentLabel');
});

it('filters by whose turn it is, and shows overdue ones', function () {
    $f = epSetup();
    $placement = $f['mine']['placement'];
    epAppraisal($placement, '2026-02', ['period_label' => 'AAA-student']);
    epAppraisal($placement, '2026-03', ['period_label' => 'BBB-supervisor', 'self_total' => 85, 'self_submitted_at' => now()]);
    epAppraisal($placement, '2026-04', ['period_label' => 'CCC-you', 'self_submitted_at' => now(), 'supervisor_submitted_at' => now(), 'student_comment_submitted_at' => now()]);
    epAppraisal($placement, '2026-05', ['period_label' => 'DDD-done', 'self_submitted_at' => now(), 'supervisor_submitted_at' => now(), 'student_comment_submitted_at' => now(), 'adviser_submitted_at' => now(), 'adviser_total' => 88]);
    epAppraisal($placement, '2026-06', ['period_label' => 'EEE-late', 'due_on' => now()->subDays(3)->toDateString()]);
    $this->actingAs($f['coordinatorUser']);

    foreach (['waiting_student' => ['AAA-student', 'EEE-late'], 'waiting_supervisor' => ['BBB-supervisor'], 'waiting_coordinator' => ['CCC-you'], 'complete' => ['DDD-done'], 'overdue' => ['EEE-late']] as $status => $labels) {
        $html = $this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'status' => $status]))->assertOk()->getContent();
        foreach (['AAA-student', 'BBB-supervisor', 'CCC-you', 'DDD-done', 'EEE-late'] as $label) {
            in_array($label, $labels, true) ? expect($html)->toContain($label) : expect($html)->not->toContain($label);
        }
    }
    $this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'status' => 'overdue']))->assertSee('Overdue');
});

it('shows the final evaluation request, the supervisor\'s submission and the review', function () {
    $f = epSetup();
    $requested = $f['mine']['placement'];
    $requested->update(['rating_requested_at' => now(), 'rating_due_on' => now()->addDays(5)->toDateString()]);
    $submitted = epPlacement($f['institution'], Program::where('code', 'BSIT')->first(), 'Cara Lim', 'Third Co');
    $submitted['placement']->update(['rating_requested_at' => now()]);
    epEvaluation($submitted['placement']);
    $done = epPlacement($f['institution'], Program::where('code', 'BSIT')->first(), 'Dan Uy', 'Fourth Co');
    epEvaluation($done['placement'], ['coordinator_review_status' => 'reviewed', 'coordinator_reviewed_at' => now()]);
    $this->actingAs($f['coordinatorUser']);

    $page = $this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'type' => 'final']))->assertOk();
    $page->assertSee('Ana Cruz')->assertSee('Cara Lim')->assertSee('Dan Uy')
        ->assertSee('Not rated yet')              // requested, supervisor has not rated
        ->assertSee('Work 95% · Traits 90%')      // submitted
        ->assertSee('Reviewed')                   // complete
        ->assertSee('Final evaluations waiting for your review') // Cara's card still there to review
        ->assertDontSee('Ben Reyes');

    // The status filter works for final evaluations too; students have no part in it.
    expect($this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'type' => 'final', 'status' => 'waiting_coordinator']))->getContent())
        ->toContain('Cara Lim')->not->toContain('Ana Cruz')->not->toContain('Dan Uy');
    expect($this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'type' => 'final', 'status' => 'waiting_student']))->getContent())->not->toContain('Ana Cruz');

    // The tab badge counts what waits for the coordinator (Cara's final evaluation, plus a monthly one).
    epAppraisal($requested, '2026-03', ['self_submitted_at' => now(), 'supervisor_submitted_at' => now(), 'student_comment_submitted_at' => now()]);
    $this->get(route('coordinator.reviews.index'))->assertSee('<span class="sb-tab-count">2</span>', false);
});

it('keeps the progress list to coordinators', function () {
    $f = epSetup();
    foreach ([$f['mine']['studentUser'], $f['mine']['employerUser']] as $user) {
        $this->actingAs($user)->get(route('coordinator.reviews.index', ['tab' => 'evaluations']))->assertForbidden();
        $this->post(route('coordinator.reviews.evaluations.remind'), ['type' => 'monthly', 'id' => 1])->assertForbidden();
    }
});

it('reminds whoever\'s turn it is, once a day, and only for the coordinator\'s own department', function () {
    $f = epSetup();
    $studentTurn = epAppraisal($f['mine']['placement'], '2026-02');
    $supervisorTurn = epAppraisal($f['mine']['placement'], '2026-03', ['self_submitted_at' => now()]);
    $myTurn = epAppraisal($f['mine']['placement'], '2026-04', ['self_submitted_at' => now(), 'supervisor_submitted_at' => now(), 'student_comment_submitted_at' => now()]);
    $elsewhere = epAppraisal($f['other']['placement'], '2026-02');
    $this->actingAs($f['coordinatorUser']);
    $remind = fn (int $id, string $type = 'monthly') => $this->post(route('coordinator.reviews.evaluations.remind'), ['type' => $type, 'id' => $id]);

    $remind($studentTurn->id)->assertSessionHas('success', 'Reminder sent to Ana Cruz.');
    expect(AppNotification::where('user_id', $f['mine']['studentUser']->id)->where('title', 'like', 'Reminder:%')->count())->toBe(1);
    // Opening that notification goes to the student's monthly evaluation tab.
    $notice = AppNotification::where('user_id', $f['mine']['studentUser']->id)->latest('id')->first();
    $this->actingAs($f['mine']['studentUser'])->get(route('notifications.show', $notice))
        ->assertRedirect(route('student.ojt.index', ['placement' => $f['mine']['placement']->id, 'tab' => 'monthly-evaluation']).'#ojtDetails');
    $this->actingAs($f['coordinatorUser']);

    // The same evaluation again today: refused. Another evaluation: fine.
    $remind($studentTurn->id)->assertSessionHas('error');
    $remind($supervisorTurn->id)->assertSessionHas('success');
    expect(AppNotification::where('user_id', $f['mine']['employerUser']->id)->where('title', 'like', 'Reminder:%')->count())->toBe(1);

    // The coordinator's own turn: nobody to remind. Another department: not found.
    $remind($myTurn->id)->assertSessionHas('error');
    $remind($elsewhere->id)->assertNotFound();

    // A final evaluation nobody rated yet goes to the supervisor.
    $f['mine']['placement']->update(['rating_requested_at' => now()]);
    $remind($f['mine']['placement']->id, 'final')->assertSessionHas('success');
    expect(AppNotification::where('user_id', $f['mine']['employerUser']->id)->where('title', 'Reminder: final evaluation')->exists())->toBeTrue();
});
