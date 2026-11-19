<?php

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A company with a supervisor (Sam), one intern of Sam's and one intern without a supervisor. */
function ovSetup(): array
{
    test()->travelTo(\Carbon\CarbonImmutable::parse('2026-03-11 09:00:00', 'Asia/Manila'));
    config(['ojt_reminders.weekly_reports_from' => '2026-02-01']);
    $company = svCompany('Overview Co');
    $sam = svSupervisor($company['employer'], 'Sam Supervisor');
    $mine = svPlacement($company['employer'], $sam);
    $free = svPlacement($company['employer']);

    return [...$company, 'sam' => $sam, 'mine' => $mine, 'free' => $free, 'myName' => $mine->student->user->name, 'freeName' => $free->student->user->name];
}

function ovLog(Placement $placement, string $date, string $status = AttendanceLog::STATUS_PENDING, float $hours = 8): AttendanceLog
{
    return $placement->attendanceLogs()->create(['work_date' => $date, 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'hours' => $hours, 'task_summary' => 'Coding', 'status' => $status]);
}

it('lists the five sections in the sidebar for a supervisor and for HR, and marks the open one', function () {
    $f = ovSetup();

    $html = $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'time-records'))->assertOk()->getContent();
    foreach (['progress' => 'Progress', 'time-records' => 'Time Records', 'weekly-reports' => 'Weekly Reports', 'monthly-evaluations' => 'Monthly Evaluations', 'final-evaluations' => 'Final Evaluations'] as $key => $label) {
        expect($html)->toContain('href="'.route('supervisor.ojt.overview', $key).'"')->and($html)->toContain($label);
    }
    // Only the open section is highlighted.
    expect(preg_match_all('/class="sb-nav-link active"\s+href="([^"]+)"/', $html, $m))->toBe(1)
        ->and($m[1][0])->toBe(route('supervisor.ojt.overview', 'time-records'));

    $this->actingAs($f['hrUser'])->get(route('employer.ojt.overview', 'progress'))->assertOk()
        ->assertSee('href="'.route('employer.ojt.overview', 'final-evaluations').'"', false);
});

it('shows a supervisor only their own interns and HR the whole company, never pending ones or other companies', function () {
    $f = ovSetup();
    $pending = svPlacement($f['employer']);
    $pending->update(['status' => Placement::STATUS_PENDING_VALIDATION]);
    $other = svPlacement(svCompany('Other Co')['employer']);

    $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'progress'))->assertOk()
        ->assertSee($f['myName'])->assertDontSee($f['freeName'])->assertDontSee($pending->student->user->name)->assertDontSee($other->student->user->name);

    $this->actingAs($f['hrUser'])->get(route('employer.ojt.overview', 'progress'))->assertOk()
        ->assertSee($f['myName'])->assertSee($f['freeName'])->assertDontSee($pending->student->user->name)->assertDontSee($other->student->user->name)
        ->assertSee('No supervisor assigned');
});

it('progress shows the hours and what holds an intern back', function () {
    $f = ovSetup();
    ovLog($f['mine'], '2026-03-02', AttendanceLog::STATUS_APPROVED, 8);
    ovLog($f['mine'], '2026-03-03', AttendanceLog::STATUS_PENDING, 6);

    $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'progress'))->assertOk()
        ->assertSee($f['myName'])->assertSee('8.0 / 500 h')->assertSee('6 h waiting for approval')
        ->assertSee('1 time record waiting for approval')
        ->assertSee('No time logged in the last 7 days')
        // The row opens the intern's own Progress tab.
        ->assertSee(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'progress']), false);
});

it('time records name each student and what to fix: waiting, missing and rejected records', function () {
    $f = ovSetup();
    ovLog($f['mine'], '2026-03-09', AttendanceLog::STATUS_PENDING, 8);
    ovLog($f['mine'], '2026-03-10', AttendanceLog::STATUS_REJECTED, 4);

    $page = $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'time-records'))->assertOk();
    $page->assertSee($f['myName'])->assertSee('1 time record waiting for approval (8 h)')->assertSee('1 rejected time record')
        ->assertSee(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'time-record']), false)
        ->assertDontSee('No time record yet');

    // HR sees the intern who never logged anything.
    $this->actingAs($f['hrUser'])->get(route('employer.ojt.overview', 'time-records'))->assertOk()
        ->assertSee($f['freeName'])->assertSee('No time record yet');
});

it('weekly reports list the weeks not submitted and the reports waiting to be certified', function () {
    $f = ovSetup();
    JournalEntry::create(['placement_id' => $f['mine']->id, 'week_start' => '2026-02-02', 'week_number' => 1, 'title' => 'Week 1', 'accomplishments' => 'x', 'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => JournalEntry::STATUS_SUBMITTED]);

    $page = $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'weekly-reports'))->assertOk();
    $page->assertSee($f['myName'])
        ->assertSee('Week 2 report not submitted')->assertSee('overdue since')
        ->assertSee('more weekly report')
        ->assertSee('1 report waiting to be certified')
        ->assertSee('Waiting to be certified')
        ->assertSee(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'weekly-journal']), false);
});

it('monthly evaluations show whose turn it is and when no evaluation was requested', function () {
    $f = ovSetup();
    Appraisal::create([
        'placement_id' => $f['mine']->id, 'period_month' => '2026-02-01', 'period_label' => 'Midterm', 'period_start' => '2026-02-02', 'period_end' => '2026-02-28',
        'due_on' => '2026-03-20', 'self_submitted_at' => now(),
    ]);

    $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'monthly-evaluations'))->assertOk()
        ->assertSee($f['myName'])->assertSee('Midterm')->assertSee('Waiting for the supervisor')
        ->assertSee('waiting for the supervisor, due Mar 20')
        ->assertSee(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'monthly-evaluation']), false);

    $this->actingAs($f['hrUser'])->get(route('employer.ojt.overview', 'monthly-evaluations'))->assertOk()
        ->assertSee($f['freeName'])->assertSee('No monthly evaluation requested yet');
});

it('final evaluations show hours still to render, a pending request and a submitted evaluation', function () {
    $f = ovSetup();
    $done = svPlacement($f['employer'], $f['sam']);
    Evaluation::create(['placement_id' => $done->id, 'form_version' => 2, 'scores' => [], 'average_score' => 4.5, 'submitted_at' => now()]);
    $requested = svPlacement($f['employer'], $f['sam']);
    $requested->update(['rating_requested_at' => now()->subDay(), 'rating_due_on' => '2026-03-20']);

    $page = $this->actingAs($f['sam'])->get(route('supervisor.ojt.overview', 'final-evaluations'))->assertOk();
    $page->assertSee($f['myName'])->assertSee('500 h still to render before the final evaluation')
        ->assertSee($done->student->user->name)->assertSee('4.50 / 5')->assertSee('waiting for the coordinator to review')
        ->assertSee($requested->student->user->name)->assertSee('waiting for the supervisor\'s rating, due Mar 20')
        ->assertSee(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'evaluation']), false);
});

it('keeps the pages to supervisors and the HR account, and refuses an unknown section', function () {
    $f = ovSetup();

    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('supervisor.ojt.overview', 'progress'))->assertForbidden();
        $this->actingAs($user)->get(route('employer.ojt.overview', 'progress'))->assertForbidden();
    }
    // A supervisor cannot use the HR pages, and the other way round.
    $this->actingAs($f['sam'])->get(route('employer.ojt.overview', 'progress'))->assertForbidden();
    $this->actingAs($f['hrUser'])->get(route('supervisor.ojt.overview', 'progress'))->assertForbidden();

    $this->actingAs($f['sam'])->get('/supervisor/ojt/nonsense')->assertNotFound();
});
