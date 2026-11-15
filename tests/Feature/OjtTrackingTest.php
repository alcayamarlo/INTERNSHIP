<?php

use App\Enums\ApplicationStatus;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\AttendanceLog;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\ReportExportService;
use App\Services\WeeklyReportService;
use App\Support\UploadDisks;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['queue.default' => 'sync', 'mail.default' => 'log']);
});

function ojtFixture(ApplicationStatus $status = ApplicationStatus::Interview): array
{
    $institution = Institution::create(['name' => 'OJT University', 'address' => 'Cebu', 'contact_email' => 'ojt@example.edu']);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'OJT Labs', 'contact_person' => 'Sam Supervisor']);

    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Olivia Intern', 'phone' => '09171234567']);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program' => 'BS Information Technology', 'student_id_number' => sprintf('SCC-21-%08d', random_int(1, 99999999))]);

    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);

    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Dev Intern', 'description' => 'Build things.', 'status' => 'open']);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => $status,
        'match_percentage' => 80,
    ]);

    return compact('employerUser', 'studentUser', 'coordinatorUser', 'application', 'student', 'employer', 'internship');
}

function ojtPlacement(array $fixture, int $requiredHours = 16): Placement
{
    $fixture['application']->update(['status' => ApplicationStatus::Accepted]);

    return Placement::create([
        'application_id' => $fixture['application']->id,
        'student_id' => $fixture['student']->id,
        'employer_id' => $fixture['employer']->id,
        'internship_id' => $fixture['internship']->id,
        'start_date' => now()->subDays(10)->toDateString(),
        'required_hours' => $requiredHours,
        'status' => Placement::STATUS_ACTIVE,
        'rating_requested_at' => now(),
        'department' => 'IT Department', // the printed Designation
    ]);
}

/** The school's Final Performance Evaluation payload: full marks, or a fixed value capped per factor. */
function ojtFinal(array $overrides = [], ?int $each = null): array
{
    $rate = fn (array $factors) => collect($factors)->map(fn ($factor) => $each === null ? $factor['points'] : min($each, $factor['points']))->all();

    return [
        'department_assigned' => 'IT Department',
        'field_of_training' => 'Software Developer Trainee',
        'supervisor_designation' => 'Solutions Architect',
        'work_scores' => $rate(Evaluation::WORK_FACTORS),
        'trait_scores' => $rate(Evaluation::TRAIT_FACTORS),
        'comments' => 'Reliable and quick to learn.',
        ...$overrides,
    ];
}

function ojtScores(int $score = 4): array
{
    return collect(Evaluation::CRITERIA)->keys()->mapWithKeys(fn ($key) => [$key => $score])->all();
}

it('opens a placement with the configured required hours when an employer accepts an application', function () {
    AcademicSetting::current()->update(['required_ojt_hours' => 486]);
    $fixture = ojtFixture();

    $this->actingAs($fixture['employerUser'])
        ->put(route('employer.applicants.status', $fixture['application']), ['status' => ApplicationStatus::Accepted->value])
        ->assertSessionHas('success');

    $placement = Placement::firstWhere('application_id', $fixture['application']->id);
    expect($placement)->not->toBeNull()
        ->and($placement->required_hours)->toBe(486)
        // The coordinator validates the placement before hours can be logged.
        ->and($placement->isPendingValidation())->toBeTrue();

    expect(ApplicationStatus::Accepted->allowedNextStatuses())->toBe([]);

    $this->actingAs($fixture['studentUser'])->get(route('student.ojt.index'))
        ->assertOk()->assertSee('Web Dev Intern')->assertSee('486 required hours');
    $this->actingAs($fixture['employerUser'])->get(route('employer.interns.index'))
        ->assertOk()->assertSee('Olivia Intern');
});

it('lets a student log time and computes hours after breaks', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);

    $this->actingAs($fixture['studentUser'])
        ->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->subDay()->toDateString(),
            'time_in' => '08:00',
            'time_out' => '17:00',
            'break_minutes' => 60,
            'task_summary' => 'Fixed bugs in the login page.',
        ])
        ->assertSessionHas('success');

    $log = $placement->attendanceLogs()->first();
    expect((float) $log->hours)->toBe(8.0)->and($log->status)->toBe(AttendanceLog::STATUS_PENDING);
    $this->assertDatabaseHas('notifications', ['user_id' => $fixture['employerUser']->id, 'type' => 'ojt_update']);

    // Duplicate dates, future dates and inverted times are rejected.
    $this->actingAs($fixture['studentUser'])
        ->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00', 'task_summary' => 'Again',
        ])->assertSessionHasErrors('work_date');
    $this->actingAs($fixture['studentUser'])
        ->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->addDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00', 'task_summary' => 'Future',
        ])->assertSessionHasErrors('work_date');
    $this->actingAs($fixture['studentUser'])
        ->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->subDays(2)->toDateString(), 'time_in' => '12:00', 'time_out' => '08:00', 'task_summary' => 'Backwards',
        ])->assertSessionHasErrors('time_out');

    expect($placement->attendanceLogs()->count())->toBe(1);
});

it('runs the full OJT lifecycle through completion, clearance and certificate', function () {
    // A Wednesday, so "yesterday" is a weekday and the week's report has the usual Mon-Fri rows.
    $this->travelTo(CarbonImmutable::parse('2026-04-22 10:00:00', 'Asia/Manila'));
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture, 16);

    foreach ([3, 2] as $daysAgo) {
        $this->actingAs($fixture['studentUser'])->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->subDays($daysAgo)->toDateString(),
            'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60,
            'task_summary' => 'Work day '.$daysAgo,
        ])->assertSessionHas('success');
    }

    $this->actingAs($fixture['studentUser'])->post(route('student.ojt.journals.store', $placement), [
        'week_start' => now()->subDays(2)->toDateString(),
        'challenges' => 'Setting up the local environment.',
        'actions_taken' => 'Paired with a senior developer.',
        'learnings' => 'How the deployment pipeline works.',
    ])->assertSessionHas('success');
    $journal = $placement->journalEntries()->first();
    expect($journal->week_start->isMonday())->toBeTrue();

    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.journals.review', [$placement, $journal]), ['supervisor_comment' => 'Great start.'])
        ->assertSessionHas('success');
    expect($journal->fresh()->status)->toBe(JournalEntry::STATUS_ACKNOWLEDGED)
        ->and($journal->fresh()->daily_snapshot)->toHaveCount(5); // Mon-Fri frozen at certification

    // Coordinator cannot clear before completion.
    $this->actingAs($fixture['coordinatorUser'])
        ->post(route('coordinator.placements.clear', $placement))
        ->assertForbidden();

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal(['comments' => 'Excellent.']))
        ->assertSessionHas('success');
    expect($placement->fresh()->isActive())->toBeTrue();

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.attendance.approve-all', $placement))
        ->assertSessionHas('success');

    $placement->refresh();
    expect($placement->isCompleted())->toBeTrue()
        ->and($placement->approvedHours())->toBe(16.0)
        ->and($fixture['application']->fresh()->status)->toBe(ApplicationStatus::Completed);

    // Certificate stays locked until clearance.
    $this->actingAs($fixture['studentUser'])->get(route('ojt.certificate', $placement))->assertForbidden();

    $this->actingAs($fixture['coordinatorUser'])->get(route('coordinator.placements.index'))
        ->assertOk()->assertSee('Olivia Intern')->assertSee('For clearance');
    $this->actingAs($fixture['coordinatorUser'])
        ->post(route('coordinator.placements.clear', $placement), ['clearance_remarks' => 'All good.'])
        ->assertSessionHas('success');

    $placement->refresh();
    expect($placement->isCleared())->toBeTrue()->and($placement->certificate_number)->toStartWith('OJT-');

    $this->actingAs($fixture['studentUser'])->get(route('ojt.certificate', $placement))
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    // Locked once cleared.
    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal([], 1))
        ->assertSessionHas('error');
    expect((float) $placement->evaluation->fresh()->average_score)->toBe(5.0);
});

it('blocks an employer from submitting a rating until the coordinator requests one', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['rating_requested_at' => null, 'rating_requested_by_user_id' => null]);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal())
        ->assertSessionHas('error');

    expect($placement->fresh()->evaluation)->toBeNull();

    $this->actingAs($fixture['coordinatorUser'])
        ->post(route('coordinator.placements.request-rating', $placement))
        ->assertSessionHas('success');

    expect($placement->fresh()->rating_requested_at)->not->toBeNull()
        ->and($placement->fresh()->rating_requested_by_user_id)->toBe($fixture['coordinatorUser']->id);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $fixture['employerUser']->id,
        'type' => 'ojt_update',
        'title' => 'Rating requested',
    ]);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal())
        ->assertSessionHas('success');

    expect($placement->fresh()->evaluation)->not->toBeNull();
});

it('denies a coordinator from requesting a rating for a student outside their institution', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['rating_requested_at' => null, 'rating_requested_by_user_id' => null]);
    $outsider = ojtFixture();

    $this->actingAs($outsider['coordinatorUser'])
        ->post(route('coordinator.placements.request-rating', $placement))
        ->assertForbidden();

    expect($placement->fresh()->rating_requested_at)->toBeNull();
});

it('denies a coordinator from requesting or viewing a rating for a student assigned to another coordinator\'s program', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);

    $otherCoordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $otherCoordinator = Coordinator::create([
        'user_id' => $otherCoordinatorUser->id,
        'institution_id' => $fixture['student']->institution_id,
    ]);
    $program = Program::create([
        'name' => 'Computer Science',
        'code' => 'BSCS',
        'required_internship_hours' => 486,
        'coordinator_id' => $otherCoordinator->id,
    ]);
    $fixture['student']->update(['program_id' => $program->id]);

    // Same institution, but the student's program is assigned to a different coordinator.
    $this->actingAs($fixture['coordinatorUser'])
        ->post(route('coordinator.placements.request-rating', $placement))
        ->assertForbidden();
    $this->actingAs($fixture['coordinatorUser'])
        ->get(route('coordinator.placements.show', $placement))
        ->assertForbidden();

    $this->actingAs($otherCoordinatorUser)
        ->post(route('coordinator.placements.request-rating', $placement))
        ->assertSessionHas('success');
});

it('keeps a pre-existing 1-5 evaluation visible and unchanged, even without a formal rating request', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['rating_requested_at' => null, 'rating_requested_by_user_id' => null]);
    Evaluation::create([
        'placement_id' => $placement->id,
        'evaluator_user_id' => $fixture['employerUser']->id,
        'scores' => ojtScores(4),
        'average_score' => 4,
        'would_hire' => true,
        'submitted_at' => now(),
    ]);

    // A rating submitted on the old 1-5 form still shows, but is never rewritten (kept as is).
    $this->actingAs($fixture['employerUser'])
        ->get(route('employer.interns.show', $placement))
        ->assertOk()
        ->assertSee('kept as is')
        ->assertSee('Final Evaluation</h2>', false)
        ->assertDontSee('No final evaluation requested yet')
        ->assertDontSee('Submit final evaluation');

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal(['comments' => 'Updated.']))
        ->assertSessionHas('error');

    $evaluation = $placement->fresh()->evaluation;
    expect($evaluation->isLegacy())->toBeTrue()
        ->and($evaluation->comments)->toBeNull()
        ->and((float) $evaluation->average_score)->toBe(4.0);
});

it('never shows the employer rating to the student, even after one is submitted', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal(['comments' => 'Secret rating comment']))
        ->assertSessionHas('success');

    $response = $this->actingAs($fixture['studentUser'])->get(route('student.ojt.index'))->assertOk();
    $response->assertDontSee('Final Evaluation</h2>', false)
        ->assertDontSee('Secret rating comment')
        ->assertDontSee('ojt-evaluation', false);

    // A student hitting the employer-only rating endpoint is rejected by role middleware.
    $this->actingAs($fixture['studentUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal())
        ->assertForbidden();
});

it('requires a remark when rejecting a time entry and does not count rejected hours', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $log = $placement->attendanceLogs()->create([
        'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00',
        'hours' => 4, 'task_summary' => 'Stuff', 'status' => AttendanceLog::STATUS_PENDING,
    ]);

    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.attendance.review', [$placement, $log]), ['status' => 'rejected'])
        ->assertSessionHasErrors('reviewer_remarks');

    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.attendance.review', [$placement, $log]), ['status' => 'rejected', 'reviewer_remarks' => 'Not on site.'])
        ->assertSessionHas('success');

    expect($log->fresh()->status)->toBe('rejected')->and($placement->approvedHours())->toBe(0.0);
});

it('blocks other users from viewing or changing a placement', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);

    $other = ojtFixture();
    $otherPlacement = ojtPlacement($other);

    $this->actingAs($other['employerUser'])->get(route('employer.interns.show', $placement))->assertForbidden();
    $this->actingAs($other['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal())
        ->assertForbidden();

    $this->actingAs($other['studentUser'])
        ->post(route('student.ojt.attendance.store', $placement), [
            'work_date' => now()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00', 'task_summary' => 'x',
        ])->assertForbidden();

    $this->actingAs($other['coordinatorUser'])->get(route('coordinator.placements.show', $placement))->assertForbidden();
    $this->actingAs($fixture['coordinatorUser'])->get(route('coordinator.placements.show', $placement))->assertOk();

    // A log from another placement cannot be reviewed through this placement.
    $foreignLog = $otherPlacement->attendanceLogs()->create([
        'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00',
        'hours' => 4, 'task_summary' => 'x', 'status' => AttendanceLog::STATUS_PENDING,
    ]);
    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.attendance.review', [$placement, $foreignLog]), ['status' => 'approved'])
        ->assertNotFound();

    $this->actingAs($fixture['studentUser'])->get(route('employer.interns.index'))->assertForbidden();
    $this->actingAs($fixture['studentUser'])->get(route('admin.placements.index'))->assertForbidden();
});

it('locks hours after completion and clearance', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture, 8);
    $approved = $placement->attendanceLogs()->create([
        'work_date' => now()->subDays(2)->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60,
        'hours' => 8, 'task_summary' => 'Day one', 'status' => AttendanceLog::STATUS_APPROVED,
    ]);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal())
        ->assertSessionHas('success');
    expect($placement->fresh()->isCompleted())->toBeTrue();

    // Completed: approved entries cannot be rejected, so hours never drop below the target.
    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.attendance.review', [$placement, $approved]), ['status' => 'rejected', 'reviewer_remarks' => 'Oops'])
        ->assertSessionHas('error');
    expect($approved->fresh()->isApproved())->toBeTrue();

    // Cleared: bulk approve is locked too, even when posted directly.
    $placement->update(['cleared_at' => now(), 'certificate_number' => 'OJT-TEST-1']);
    $pending = $placement->attendanceLogs()->create([
        'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '12:00',
        'hours' => 4, 'task_summary' => 'Late entry', 'status' => AttendanceLog::STATUS_PENDING,
    ]);
    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.attendance.approve-all', $placement))
        ->assertSessionHas('error');
    expect($pending->fresh()->status)->toBe(AttendanceLog::STATUS_PENDING);
});

it('notifies the coordinator when an OJT completes', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture, 8);
    $placement->attendanceLogs()->create([
        'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60,
        'hours' => 8, 'task_summary' => 'Day', 'status' => AttendanceLog::STATUS_APPROVED,
    ]);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal());

    $this->assertDatabaseHas('notifications', [
        'user_id' => $fixture['coordinatorUser']->id,
        'type' => 'ojt_update',
        'title' => 'OJT ready for clearance',
    ]);
});

it('denies instead of crashing when the placement student was deleted', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $fixture['student']->delete();

    $this->actingAs($fixture['coordinatorUser'])
        ->get(route('coordinator.placements.show', $placement->fresh()))
        ->assertForbidden();
});

it('shows a start-date notice instead of the DTR form before the OJT starts', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['start_date' => now()->addWeek()->toDateString()]);

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.ojt.index'))
        ->assertOk()
        ->assertSee('Your OJT starts on')
        ->assertDontSee('Submit time entry');
});

it('limits coordinator OJT and student reports to their own students', function () {
    Storage::fake('private');
    $mine = ojtFixture();
    ojtPlacement($mine);
    $theirs = ojtFixture();
    $theirs['studentUser']->update(['name' => 'Other School Student']);
    ojtPlacement($theirs);

    $service = app(ReportExportService::class);
    $read = fn ($report) => Storage::disk(UploadDisks::private())->get($report->file_path);

    foreach (['ojt', 'student', 'placement'] as $type) {
        $csv = $read($service->generate($mine['coordinatorUser'], $type, 'csv'));
        expect($csv)->toContain('Olivia Intern')->not->toContain('Other School Student');
    }

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    expect($read($service->generate($admin, 'ojt', 'csv')))->toContain('Other School Student');
});

it('seeds demo OJT data that renders for every role', function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);

    $ongoing = User::where('email', 'student2@skillbridge.test')->first();
    $cleared = User::where('email', 'student3@skillbridge.test')->first();
    $clearedPlacement = $cleared->student->placements()->first();

    expect(Placement::count())->toBe(2)
        ->and($ongoing->student->placements()->first()->isActive())->toBeTrue()
        ->and($clearedPlacement->isCleared())->toBeTrue()
        ->and($clearedPlacement->hasMetRequiredHours())->toBeTrue();

    $this->actingAs($ongoing)->get(route('student.dashboard'))->assertOk()->assertSee('OJT Progress')->assertSee('OJT requirements reminder');
    $this->actingAs($ongoing)->get(route('student.ojt.index'))->assertOk()->assertSee('Daily Time Record');
    $this->actingAs($cleared)->get(route('ojt.certificate', $clearedPlacement))->assertOk();

    foreach (['employer' => ['employer.dashboard', 'employer.interns.index'], 'coordinator' => ['coordinator.dashboard', 'coordinator.placements.index'], 'admin' => ['admin.dashboard', 'admin.placements.index']] as $role => $routes) {
        $user = User::where('email', $role.'@skillbridge.test')->first();
        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    $employer = User::where('email', 'employer@skillbridge.test')->first();
    $this->actingAs($employer)->get(route('employer.interns.show', $clearedPlacement))->assertOk()->assertSee('evaluation is locked');
});

it('shows placements to admins and lets them set the required hours', function () {
    $fixture = ojtFixture();
    ojtPlacement($fixture);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.placements.index'))->assertOk()->assertSee('Olivia Intern');
    $this->actingAs($admin)->get(route('admin.academics.index'))->assertOk()->assertSee('Required OJT hours');
});

it('opens the tab from the URL and keeps each tab limited to its own panel', function () {
    $fixture = ojtFixture();
    ojtPlacement($fixture);

    $page = fn (string $query = '') => $this->actingAs($fixture['studentUser'])->get(route('student.ojt.index').$query)->assertOk();

    // Default and invalid values open Progress; the other panels are rendered hidden.
    foreach (['', '?tab=nonsense'] as $query) {
        $page($query)->assertSee('id="ojt-tab-progress" class="ojt-tab"', false)
            ->assertSeeInOrder(['id="ojt-panel-progress"', 'aria-labelledby="ojt-tab-progress"'], false)
            ->assertSee('aria-selected="true"', false);
    }
    $html = $page('?tab=time-record')->getContent();
    expect($html)->toMatch('/id="ojt-panel-progress"[^>]*\shidden/')
        ->and($html)->not->toMatch('/id="ojt-panel-time-record"[^>]*\shidden/')
        ->and($html)->toMatch('/id="ojt-panel-weekly-journal"[^>]*\shidden/');

    // The "add" buttons sit on the right of the tab bar, and only the open tab's button is visible.
    expect($html)->toMatch('/data-ojt-action="time-record"[^>]*data-bs-target="#dailyEntryModal"(?![^>]*\shidden)/')
        ->and($html)->toMatch('/data-ojt-action="weekly-journal"[^>]*\shidden/')
        ->and(Str::between($html, 'id="ojt-panel-progress"', 'id="ojt-panel-weekly-journal"'))->not->toContain('Add weekly journal');
});

// ---------- Student dashboard: OJT progress replaces recommendations once placed ----------

function ojtDashboardLog(Placement $placement, float $hours, string $status, int $daysAgo): void
{
    AttendanceLog::create([
        'placement_id' => $placement->id,
        'work_date' => now()->subDays($daysAgo)->toDateString(),
        'time_in' => '08:00',
        'time_out' => '17:00',
        'break_minutes' => 60,
        'hours' => $hours,
        'task_summary' => 'Worked on tickets.',
        'status' => $status,
    ]);
}

it('keeps Recommended for You on the dashboard while the student has no placement', function () {
    $fixture = ojtFixture();

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Recommended for You')
        ->assertSee('Browse Internships')
        ->assertSee('Recent Applications')
        ->assertSee('Active Applications')
        ->assertDontSee('View full OJT progress')
        ->assertDontSee('Log OJT Time');
});

it('replaces recommendations with one OJT progress card per placed company', function () {
    $fixture = ojtFixture();
    $ongoing = ojtPlacement($fixture, 400);
    ojtDashboardLog($ongoing, 160, AttendanceLog::STATUS_APPROVED, 3);
    ojtDashboardLog($ongoing, 8, AttendanceLog::STATUS_PENDING, 2);

    // A second company, already finished.
    $otherEmployer = Employer::create(['user_id' => User::factory()->create(['role' => UserRole::Employer])->id, 'company_name' => 'Second Co']);
    $otherInternship = Internship::create(['employer_id' => $otherEmployer->id, 'title' => 'QA Intern', 'description' => 'Test things.', 'status' => 'open']);
    $otherApplication = InternshipApplication::create(['internship_id' => $otherInternship->id, 'student_id' => $fixture['student']->id, 'status' => ApplicationStatus::Completed, 'match_percentage' => 70]);
    $finished = Placement::create([
        'application_id' => $otherApplication->id,
        'student_id' => $fixture['student']->id,
        'employer_id' => $otherEmployer->id,
        'internship_id' => $otherInternship->id,
        'start_date' => now()->subDays(40)->toDateString(),
        'required_hours' => 16,
        'status' => Placement::STATUS_COMPLETED,
    ]);
    ojtDashboardLog($finished, 16, AttendanceLog::STATUS_APPROVED, 30);

    $response = $this->actingAs($fixture['studentUser'])->get(route('student.dashboard'))->assertOk();

    $response->assertDontSee('Recommended for You')
        ->assertDontSee('Browse Internships')
        // Other applications no longer matter once placed.
        ->assertDontSee('Recent Applications')
        ->assertDontSee('Active Applications')
        ->assertSeeInOrder(['OJT Progress', 'Profile Completion', 'Getting Started', 'Log Daily Time', 'Quick Actions'])
        ->assertSee('Log OJT Time')
        ->assertSee('2 companies')
        // The ongoing placement comes first.
        ->assertSeeInOrder(['OJT Labs', 'Web Dev Intern', 'Ongoing', '160</strong> / 400 hrs', '40%', '8 hrs', '240 hrs', 'Not yet'], false)
        ->assertSeeInOrder(['Second Co', 'QA Intern', 'Completed', '16</strong> / 16 hrs', '100%', 'Finished'], false)
        ->assertSee(route('student.ojt.index', ['placement' => $ongoing->id]), false)
        ->assertSee(route('student.ojt.index', ['placement' => $finished->id]), false);

    // Same numbers as My OJT > Progress.
    expect($ongoing->approvedHours())->toBe(160.0)
        ->and($ongoing->pendingHours())->toBe(8.0)
        ->and($ongoing->progressPercent())->toBe(40);
});

it('tells a newly placed student that no hours are logged yet', function () {
    $fixture = ojtFixture();
    ojtPlacement($fixture, 400);

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('0</strong> / 400 hrs', false)
        ->assertSee('No hours logged yet.')
        ->assertSee('Not yet');
});

// ---------- One OJT per student: no more applications once placed ----------

it('blocks a placed student from applying to any other internship, directly or through an invitation', function () {
    $fixture = ojtFixture();
    ojtPlacement($fixture);
    $student = $fixture['student'];

    $otherEmployer = Employer::create(['user_id' => User::factory()->create(['role' => UserRole::Employer])->id, 'company_name' => 'Another Co']);
    $other = Internship::create(['employer_id' => $otherEmployer->id, 'title' => 'Data Intern', 'description' => 'Data work.', 'status' => 'open']);
    $resume = Resume::create(['student_id' => $student->id, 'file_path' => 'resumes/cv.pdf', 'content' => [], 'generated_at' => now()]);
    $message = ApplicationService::HAS_OJT_MESSAGE;

    // Direct apply is refused by the server, whatever the page shows.
    $this->actingAs($fixture['studentUser'])
        ->postJson(route('student.internships.apply', $other), ['resume_id' => $resume->id])
        ->assertStatus(422)
        ->assertJsonPath('message', $message);

    // Accepting an invitation would apply too, so it is refused the same way.
    $invitation = InternshipInvitation::create([
        'employer_id' => $otherEmployer->id,
        'student_id' => $student->id,
        'internship_id' => $other->id,
        'invited_by_user_id' => $otherEmployer->user_id,
        'status' => InvitationStatus::Pending,
        'expires_at' => now()->addWeek(),
    ]);
    $this->post(route('student.invitations.accept', $invitation))
        ->assertRedirect(route('student.invitations.index'))
        ->assertSessionHas('error', $message);

    expect(InternshipApplication::where('internship_id', $other->id)->exists())->toBeFalse();

    // The pages say why instead of offering Apply / Accept.
    $this->get(route('student.internships.index'))->assertOk()->assertSee($message)->assertDontSee('data-apply-id="'.$other->id.'"', false);
    $this->get(route('student.internships.show', $other))->assertOk()->assertSee($message)->assertDontSee('id="idApplyModal"', false);
    $this->get(route('student.invitations.index'))->assertOk()->assertSee($message)->assertDontSee('action="'.route('student.invitations.accept', $invitation).'"', false);
});

it('still lets a student without an OJT apply', function () {
    $fixture = ojtFixture();
    $fixture['student']->forceFill(['year_level' => '4th Year', 'major_subjects_passed' => true])->save();
    $resume = Resume::create(['student_id' => $fixture['student']->id, 'file_path' => 'resumes/cv.pdf', 'content' => [], 'generated_at' => now()]);
    $other = Internship::create(['employer_id' => $fixture['employer']->id, 'title' => 'Second Role', 'description' => 'More work.', 'status' => 'open']);

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.internships.index'))
        ->assertOk()
        ->assertDontSee(ApplicationService::HAS_OJT_MESSAGE);
    $this->postJson(route('student.internships.apply', $other), ['resume_id' => $resume->id])->assertOk();
});

// ---------------------------------------------------------------------------
// Phase 3: Final Performance Evaluation (school form) and weekly Activity Narrative Report
// ---------------------------------------------------------------------------

/** A time record for one day of a placement. */
function ojtLog(Placement $placement, string $date, float $hours, string $task, string $status = AttendanceLog::STATUS_APPROVED): AttendanceLog
{
    return AttendanceLog::create([
        'placement_id' => $placement->id, 'work_date' => $date, 'time_in' => '08:00', 'time_out' => '17:00',
        'break_minutes' => 60, 'hours' => $hours, 'task_summary' => $task, 'status' => $status,
    ]);
}

function ojtReport(array $overrides = []): array
{
    return [
        'week_start' => now()->toDateString(),
        'challenges' => 'Mixed emotions while wrapping up.',
        'actions_taken' => 'Finished pending tasks and documented my work.',
        'learnings' => 'Proper task completion and accountability.',
        ...$overrides,
    ];
}

it('saves the school final evaluation, maps it to the 1-5 scale, and completes the OJT', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture, 8);
    ojtLog($placement, now()->subDay()->toDateString(), 8, 'Built the login page.');

    // Max rating per factor is enforced, and the text fields are required.
    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ojtFinal(['work_scores' => [...ojtFinal()['work_scores'], 'knowledge' => 26]]))
        ->assertSessionHasErrors('work_scores.knowledge');
    foreach (['department_assigned', 'field_of_training', 'supervisor_designation', 'comments'] as $field) {
        $this->post(route('employer.interns.evaluation.store', $placement), ojtFinal([$field => '']))->assertSessionHasErrors($field);
    }

    // Work 95% (punctuality 8, attendance 12) and traits 100% => ((95 + 100) / 2) / 20 = 4.875.
    $payload = ojtFinal(['certified_by_name' => 'Arvin Diaz', 'certified_by_position' => 'HR Officer']);
    $payload['work_scores']['punctuality'] = 8;
    $payload['work_scores']['attendance'] = 12;
    $this->post(route('employer.interns.evaluation.store', $placement), $payload)->assertSessionHas('success');

    $evaluation = $placement->fresh()->evaluation;
    expect($evaluation->form_version)->toBe(Evaluation::FORM_SCHOOL)
        ->and($evaluation->work_total)->toBe(95)
        ->and($evaluation->trait_total)->toBe(100)
        ->and((float) $evaluation->average_score)->toBe(4.88)
        ->and($evaluation->supervisor_designation)->toBe('Solutions Architect')
        ->and($placement->fresh()->department)->toBe('IT Department')
        ->and($fixture['employer']->fresh()->position)->toBe('Solutions Architect') // saved back to a blank profile
        ->and($placement->fresh()->isCompleted())->toBeTrue();

    // A later change to the profile title does not alter the signed snapshot.
    $fixture['employer']->update(['position' => 'CTO']);
    expect($evaluation->fresh()->supervisor_designation)->toBe('Solutions Architect');

    // Printable for the student, the supervisor and the coordinator; not for another company.
    foreach ([$fixture['studentUser'], $fixture['employerUser'], $fixture['coordinatorUser']] as $allowed) {
        $this->actingAs($allowed)->get(route('ojt.evaluations.print', $placement))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
    $this->actingAs(ojtFixture()['employerUser'])->get(route('ojt.evaluations.print', $placement))->assertForbidden();
});

it('still renders an old 1-5 evaluation unchanged and has no school-form PDF for it', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    Evaluation::create(['placement_id' => $placement->id, 'evaluator_user_id' => $fixture['employerUser']->id, 'scores' => ojtScores(4),
        'average_score' => 4, 'strengths' => 'Punctual', 'would_hire' => true, 'submitted_at' => now()]);

    $this->actingAs($fixture['coordinatorUser'])->get(route('coordinator.placements.show', $placement))->assertOk()
        ->assertSee('Final Evaluation</h2>', false)->assertSee('4.00')->assertSee('Strengths:')->assertDontSee('Final Performance Evaluation</h2>', false);
    $this->get(route('ojt.evaluations.print', $placement))->assertNotFound();
});

it('lets the coordinator request the final evaluation with a due date', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['rating_requested_at' => null]);
    $this->actingAs($fixture['coordinatorUser']);

    $this->post(route('coordinator.placements.request-rating', $placement), ['rating_due_on' => now()->subDay()->toDateString()])->assertSessionHasErrors('rating_due_on');
    $this->post(route('coordinator.placements.request-rating', $placement), ['rating_due_on' => now()->addDays(5)->toDateString()])->assertSessionHas('success');
    expect($placement->fresh()->rating_due_on->toDateString())->toBe(now()->addDays(5)->toDateString());

    $this->get(route('coordinator.placements.show', $placement))->assertSee('Final evaluation request')->assertSee('Re-request final evaluation');
    $this->actingAs($fixture['employerUser'])->get(route('employer.interns.show', $placement))->assertSee('Due '.now()->addDays(5)->format('M d, Y'));
});

it('numbers weekly reports from the Monday-to-Friday week containing the OJT start', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-22 10:00:00'));
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['start_date' => '2026-01-28']); // a Wednesday: week 1 is Jan 26-30

    expect($placement->weekNumberFor(CarbonImmutable::parse('2026-01-30')))->toBe(1)
        ->and($placement->weekNumberFor(CarbonImmutable::parse('2026-02-02')))->toBe(2)
        ->and($placement->weekNumberFor(CarbonImmutable::parse('2026-04-24')))->toBe(13)
        ->and($placement->weekNumberFor(CarbonImmutable::parse('2026-01-23')))->toBeNull();

    $this->actingAs($fixture['studentUser'])->post(route('student.ojt.journals.store', $placement), ojtReport(['week_start' => '2026-04-21']))->assertSessionHas('success');
    $entry = $placement->journalEntries()->sole();
    expect($entry->week_number)->toBe(13)
        ->and($entry->title)->toBe('Week 13 – Activity Narrative Report')
        ->and($entry->inclusiveDates())->toBe('April 20 - 24, 2026')
        ->and(app(WeeklyReportService::class)->periodLabel($entry))->toBe('FINALS (Week 13)');

    // Before the OJT start, and the same week twice, are refused.
    $this->post(route('student.ojt.journals.store', $placement), ojtReport(['week_start' => '2026-01-20']))->assertSessionHasErrors('week_start');
    $this->post(route('student.ojt.journals.store', $placement), ojtReport(['week_start' => '2026-04-23']))->assertSessionHasErrors('week_start');
});

it('requires the three narrative sections', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $this->actingAs($fixture['studentUser']);

    foreach (['challenges', 'actions_taken', 'learnings'] as $section) {
        $this->post(route('student.ojt.journals.store', $placement), ojtReport([$section => '']))->assertSessionHasErrors($section);
    }
    expect($placement->journalEntries()->count())->toBe(0);
});

it('builds Mon-Fri rows with absences, freezes them at certification, and allows reopening', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-25 10:00:00'));
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['start_date' => '2026-01-28']);
    ojtLog($placement, '2026-04-20', 8.05, 'Continued work on the remaining features of the system.');
    ojtLog($placement, '2026-04-22', 8.35, 'Finalization of the Attendance Management System.');
    ojtLog($placement, '2026-04-23', 8, 'Continued work on the remaining features of the system');
    ojtLog($placement, '2026-04-24', 5.7, 'Awarding of the Certificate of Completion');
    ojtLog($placement, '2026-04-21', 9, 'Rejected entry', AttendanceLog::STATUS_REJECTED); // not part of the record

    $this->actingAs($fixture['studentUser'])->post(route('student.ojt.journals.store', $placement), ojtReport(['week_start' => '2026-04-20']))->assertSessionHas('success');
    $entry = $placement->journalEntries()->sole();
    $rows = app(WeeklyReportService::class)->dailyRows($entry);
    expect(array_column($rows, 'day'))->toBe(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
        // A day with no time record prints blank (not "-").
        ->and(array_column($rows, 'hours_label'))->toBe(['8 hrs and 3 mins', '', '8 hrs and 21 mins', '8 hrs', '5 hrs and 42 mins'])
        ->and($rows[1]['activity'])->toBe('');

    // Another company's supervisor cannot certify or print it.
    $other = ojtFixture();
    $this->actingAs($other['employerUser'])->patch(route('employer.interns.journals.review', [$placement, $entry]))->assertForbidden();
    $this->get(route('ojt.journals.print', $entry))->assertForbidden();

    // Certify: rows are frozen and the student can no longer delete it.
    $this->actingAs($fixture['employerUser'])->patch(route('employer.interns.journals.review', [$placement, $entry]), ['supervisor_comment' => 'Well done.'])->assertSessionHas('success');
    expect($entry->fresh()->isCertified())->toBeTrue()->and($entry->fresh()->daily_snapshot)->toHaveCount(5);
    AttendanceLog::where('placement_id', $placement->id)->whereDate('work_date', '2026-04-20')->update(['hours' => 2, 'task_summary' => 'Edited later']);
    $frozen = app(WeeklyReportService::class)->dailyRows($entry->fresh());
    expect($frozen[0]['hours_label'])->toBe('8 hrs and 3 mins')->and($frozen[0]['activity'])->toBe('Continued work on the remaining features of the system.');
    $this->actingAs($fixture['studentUser'])->delete(route('student.ojt.journals.destroy', [$placement, $entry]))->assertSessionHas('error');
    expect(JournalEntry::find($entry->id))->not->toBeNull();

    // The printed report renders for the student, supervisor and coordinator.
    foreach ([$fixture['studentUser'], $fixture['employerUser'], $fixture['coordinatorUser']] as $allowed) {
        $this->actingAs($allowed)->get(route('ojt.journals.print', $entry))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    // Reopen (supervisor, then coordinator after a second certification) clears the snapshot.
    $this->actingAs($fixture['employerUser'])->patch(route('employer.interns.journals.reopen', [$placement, $entry]))->assertSessionHas('success');
    expect($entry->fresh()->isCertified())->toBeFalse()->and($entry->fresh()->daily_snapshot)->toBeNull();
    expect(app(WeeklyReportService::class)->dailyRows($entry->fresh())[0]['hours_label'])->toBe('2 hrs');
    $this->patch(route('employer.interns.journals.review', [$placement, $entry]))->assertSessionHas('success');
    $this->actingAs($other['coordinatorUser'])->post(route('coordinator.reviews.journals.reopen', $entry))->assertForbidden();
    $this->actingAs($fixture['coordinatorUser'])->post(route('coordinator.reviews.journals.reopen', $entry))->assertSessionHas('success');
    expect($entry->fresh()->isCertified())->toBeFalse();
});

it('formats hours from minutes the way the school form writes them', function () {
    $format = fn (int $minutes) => WeeklyReportService::formatMinutes($minutes);

    expect($format(483))->toBe('8 hrs and 3 mins')
        ->and($format(480))->toBe('8 hrs')
        ->and($format(342))->toBe('5 hrs and 42 mins')
        ->and($format(61))->toBe('1 hr and 1 min')
        ->and($format(42))->toBe('42 mins')
        ->and($format(0))->toBe('-');
});

it('saves the supervisor job title and the trainee department', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $this->actingAs($fixture['employerUser']);

    $this->put(route('employer.interns.schedule', $placement), ['start_date' => $placement->start_date->toDateString(), 'department' => 'IT Department'])->assertSessionHas('success');
    expect($placement->fresh()->department)->toBe('IT Department');

    $this->get(route('employer.profile.edit'))->assertOk()->assertSee('Your job title');
    $this->put(route('employer.profile.update'), ['company_name' => 'OJT Labs', 'industry' => 'IT', 'position' => 'Solutions Architect'])->assertSessionHasNoErrors();
    expect($fixture['employer']->fresh()->position)->toBe('Solutions Architect');
});
