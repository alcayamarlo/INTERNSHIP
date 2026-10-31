<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\AttendanceLog;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['queue.default' => 'sync', 'mail.default' => 'log']);
});

function ojtFixture(ApplicationStatus $status = ApplicationStatus::Interview): array
{
    $institution = Institution::create(['name' => 'OJT University', 'address' => 'Cebu', 'contact_email' => 'ojt@example.edu']);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'OJT Labs', 'contact_person' => 'Sam Supervisor']);

    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Olivia Intern']);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program' => 'BS Information Technology']);

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
    ]);
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
        ->and($placement->isActive())->toBeTrue();

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
        'title' => 'First week',
        'accomplishments' => 'Onboarding and first tickets.',
    ])->assertSessionHas('success');
    $journal = $placement->journalEntries()->first();
    expect($journal->week_start->isMonday())->toBeTrue();

    $this->actingAs($fixture['employerUser'])
        ->patch(route('employer.interns.journals.review', [$placement, $journal]), ['supervisor_comment' => 'Great start.'])
        ->assertSessionHas('success');
    expect($journal->fresh()->status)->toBe(JournalEntry::STATUS_ACKNOWLEDGED);

    // Coordinator cannot clear before completion.
    $this->actingAs($fixture['coordinatorUser'])
        ->post(route('coordinator.placements.clear', $placement))
        ->assertForbidden();

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores(5), 'would_hire' => 1, 'comments' => 'Excellent.'])
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
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores(1)])
        ->assertSessionHas('error');
    expect((float) $placement->evaluation->fresh()->average_score)->toBe(5.0);
});

it('blocks an employer from submitting a rating until the coordinator requests one', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);
    $placement->update(['rating_requested_at' => null, 'rating_requested_by_user_id' => null]);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()])
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
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()])
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

it('keeps a pre-existing evaluation visible and editable even without a formal rating request', function () {
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

    // The employer can still update a rating that was submitted before this feature existed.
    $this->actingAs($fixture['employerUser'])
        ->get(route('employer.interns.show', $placement))
        ->assertOk()
        ->assertSee('Update evaluation', false)
        ->assertDontSee('No rating requested yet');

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores(5), 'comments' => 'Updated.'])
        ->assertSessionHas('success');

    expect($placement->fresh()->evaluation->comments)->toBe('Updated.');
});

it('never shows the employer rating to the student, even after one is submitted', function () {
    $fixture = ojtFixture();
    $placement = ojtPlacement($fixture);

    $this->actingAs($fixture['employerUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores(5), 'comments' => 'Secret rating comment'])
        ->assertSessionHas('success');

    $response = $this->actingAs($fixture['studentUser'])->get(route('student.ojt.index'))->assertOk();
    $response->assertDontSee('Supervisor Evaluation')
        ->assertDontSee('Secret rating comment')
        ->assertDontSee('ojt-evaluation', false);

    // A student hitting the employer-only rating endpoint is rejected by role middleware.
    $this->actingAs($fixture['studentUser'])
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()])
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
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()])
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
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()])
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
        ->post(route('employer.interns.evaluation.store', $placement), ['scores' => ojtScores()]);

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

    $service = app(\App\Services\ReportExportService::class);
    $read = fn ($report) => Storage::disk(\App\Support\UploadDisks::private())->get($report->file_path);

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
