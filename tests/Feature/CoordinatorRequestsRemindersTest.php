<?php

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const CR_DAILY = 'Reminder: log your time today';

/** A Wednesday morning (Manila). A company with Sam (a supervisor), Sam's intern, an intern HR covers, and a coordinator. */
function crSetup(): array
{
    test()->travelTo(CarbonImmutable::parse('2026-03-11 09:00:00', 'Asia/Manila'));
    config(['ojt_reminders.weekly_reports_from' => '2026-02-01']);
    $company = svCompany('Remind Co');
    $sam = svSupervisor($company['employer'], 'Sam Supervisor');
    $mine = svPlacement($company['employer'], $sam);
    $free = svPlacement($company['employer']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => Institution::first()->id]);
    assignDepartment($coordinator, $mine->student, $free->student);

    return [...$company, 'sam' => $sam, 'mine' => $mine, 'free' => $free, 'coordinator' => $coordinator, 'coordinatorUser' => $coordinatorUser,
        'mineUser' => $mine->student->user, 'freeUser' => $free->student->user];
}

/** Another intern of the company (Sam's, or not), in the coordinator's department. */
function crIntern(array $f, bool $supervised = true): Placement
{
    $placement = svPlacement($f['employer'], $supervised ? $f['sam'] : null);
    assignDepartment(Coordinator::findOrFail($f['coordinator']->id), $placement->student); // a fresh copy: its department is loaded

    return $placement;
}

function crLogToday(Placement $placement): void
{
    $placement->attendanceLogs()->create(['work_date' => now()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'hours' => 8, 'task_summary' => 'Coding', 'status' => 'pending']);
}

function crNotes(User $user, ?string $title = null): int
{
    return AppNotification::where('user_id', $user->id)->when($title, fn ($query) => $query->where('title', $title))->count();
}

function crAppraisal(Placement $placement, array $attributes = []): Appraisal
{
    return Appraisal::create([
        'placement_id' => $placement->id, 'period_month' => '2026-02-01', 'period_label' => 'Midterm', 'period_start' => '2026-02-02', 'period_end' => '2026-02-28',
        'due_on' => '2026-03-20', ...$attributes,
    ]);
}

// ------------------------------------------------------------------ daily time record, one student

it('reminds one student to log time, links the bell to Time Record, and waits 8 hours before the next reminder', function () {
    $f = crSetup();
    $url = route('coordinator.placements.remind-daily-log', $f['mine']);
    $this->actingAs($f['coordinatorUser']);

    $this->post($url)->assertSessionHas('success');
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(1);
    $notice = AppNotification::where('user_id', $f['mineUser']->id)->sole();
    expect($notice->type)->toBe('ojt_reminder');
    $this->assertDatabaseHas('system_logs', ['user_id' => $f['coordinatorUser']->id, 'action' => 'coordinator_daily_log_remind']);

    // The bell opens the student's Time Record tab.
    $this->actingAs($f['mineUser'])->get(route('notifications.show', $notice))
        ->assertRedirect(route('student.ojt.index', ['placement' => $f['mine']->id, 'tab' => 'time-record']).'#ojtDetails');
    $this->actingAs($f['coordinatorUser']);

    // Right away, and after 7 hours: refused with the time it can go out again.
    $this->post($url)->assertSessionHas('error', fn (string $message) => str_contains($message, 'less than 8 hours ago') && str_contains($message, '5:00 PM'));
    $this->travel(7)->hours();
    $this->post($url)->assertSessionHas('error');
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(1);

    // 8 hours after the first reminder it goes out again.
    $this->travel(61)->minutes();
    $this->post($url)->assertSessionHas('success');
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(2);
});

it('does not remind a student who logged today, on a weekend, outside the OJT dates, or for another department', function () {
    $f = crSetup();
    $this->actingAs($f['coordinatorUser']);

    crLogToday($f['mine']);
    $this->post(route('coordinator.placements.remind-daily-log', $f['mine']))->assertSessionHas('status', fn (string $m) => str_contains($m, 'already logged'));

    $f['free']->update(['start_date' => '2026-04-01']); // not started yet
    $this->post(route('coordinator.placements.remind-daily-log', $f['free']))->assertSessionHas('status', fn (string $m) => str_contains($m, 'not expected to log'));

    $this->travelTo(CarbonImmutable::parse('2026-03-14 09:00:00', 'Asia/Manila')); // Saturday
    $f['free']->update(['start_date' => '2026-02-02']);
    $this->post(route('coordinator.placements.remind-daily-log', $f['free']))->assertSessionHas('status', fn (string $m) => str_contains($m, 'not expected to log'));
    expect(crNotes($f['mineUser']))->toBe(0)->and(crNotes($f['freeUser']))->toBe(0);

    // A coordinator of another department cannot remind this student.
    $outsider = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $outsider->id, 'institution_id' => Institution::first()->id]);
    $this->travelTo(CarbonImmutable::parse('2026-03-11 09:00:00', 'Asia/Manila'));
    $this->actingAs($outsider)->post(route('coordinator.placements.remind-daily-log', $f['free']))->assertForbidden();
    expect(crNotes($f['freeUser']))->toBe(0);
});

// ------------------------------------------------------------------ daily time record, everyone

it('reminds every student who has not logged today, skipping those who did and those reminded in the last 8 hours', function () {
    $f = crSetup();
    $logged = crIntern($f);
    crLogToday($logged);
    $this->actingAs($f['coordinatorUser']);

    $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'students'])
        ->assertSessionHas('success', 'Reminded 2 students to log today\'s time.');
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(1)->and(crNotes($f['freeUser'], CR_DAILY))->toBe(1)->and(crNotes($logged->student->user))->toBe(0);

    // Again at once: nobody new, and the message says why.
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'students'])
        ->assertSessionHas('success', fn (string $m) => str_contains($m, 'Reminded 0 students') && str_contains($m, '2 students were reminded in the last 8 hours'));
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(1);

    $this->travel(8)->hours();
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'students'])->assertSessionHas('success', 'Reminded 2 students to log today\'s time.');
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(2);

    // Everyone logged: nothing to do.
    crLogToday($f['mine']);
    crLogToday($f['free']);
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'students'])->assertSessionHas('status', fn (string $m) => str_contains($m, 'Nobody to remind'));
});

// ------------------------------------------------------------------ daily time record, automatic

it('reminds automatically from 4 PM (8 hours into the work day), once a day, and not right after a coordinator did', function () {
    $f = crSetup();
    $logged = crIntern($f);
    crLogToday($logged);

    // 9 AM: too early.
    $this->artisan('ojt:remind-daily-logs')->expectsOutputToContain('Too early')->assertSuccessful();
    expect(crNotes($f['mineUser']))->toBe(0);

    // The coordinator reminded Claire at 2 PM: no second reminder two hours later.
    $this->travelTo(CarbonImmutable::parse('2026-03-11 14:00:00', 'Asia/Manila'));
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.placements.remind-daily-log', $f['mine']))->assertSessionHas('success');

    $this->travelTo(CarbonImmutable::parse('2026-03-11 16:00:00', 'Asia/Manila'));
    $this->artisan('ojt:remind-daily-logs')->expectsOutputToContain('Reminded 1 student(s)')->assertSuccessful();
    expect(crNotes($f['mineUser'], CR_DAILY))->toBe(1) // the coordinator's only
        ->and(crNotes($f['freeUser'], CR_DAILY))->toBe(1)
        ->and(crNotes($logged->student->user))->toBe(0);
    $auto = AppNotification::where('user_id', $f['freeUser']->id)->sole();
    expect($auto->message)->toContain('You have not logged your time for today');

    // Running again the same day sends nothing more.
    $this->travelTo(CarbonImmutable::parse('2026-03-11 17:00:00', 'Asia/Manila'));
    $this->artisan('ojt:remind-daily-logs')->expectsOutputToContain('Reminded 0 student(s)');
    expect(crNotes($f['freeUser'], CR_DAILY))->toBe(1);

    // Next morning's weekday after 4 PM reminds again; a weekend never does.
    $this->travelTo(CarbonImmutable::parse('2026-03-12 16:00:00', 'Asia/Manila'));
    $this->artisan('ojt:remind-daily-logs')->assertSuccessful();
    expect(crNotes($f['freeUser'], CR_DAILY))->toBe(2);
    $this->travelTo(CarbonImmutable::parse('2026-03-14 16:00:00', 'Asia/Manila')); // Saturday
    $this->artisan('ojt:remind-daily-logs')->expectsOutputToContain('Reminded 0 student(s)');
    expect(crNotes($f['freeUser'], CR_DAILY))->toBe(2);

    // --force runs before the reminder time (the third intern logged on Wednesday, not today).
    $this->travelTo(CarbonImmutable::parse('2026-03-16 09:00:00', 'Asia/Manila')); // Monday
    $this->artisan('ojt:remind-daily-logs', ['--force' => true])->expectsOutputToContain('Reminded 3 student(s)')->assertSuccessful();
});

// ------------------------------------------------------------------ weekly / monthly / final reminders

it('reminds whoever\'s turn it is on the monthly evaluations, one notification per person, with no limit', function () {
    $f = crSetup();
    $second = crIntern($f);
    $third = crIntern($f);
    crAppraisal($f['mine']); // waiting for the student's rating
    crAppraisal($second, ['self_submitted_at' => now()]); // waiting for Sam
    crAppraisal($third, ['self_submitted_at' => now()]); // waiting for Sam
    crAppraisal($f['free'], ['self_submitted_at' => now()]); // waiting for HR (no supervisor)
    $this->actingAs($f['coordinatorUser']);

    // Each button reminds only its own group.
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly', 'audience' => 'students'])
        ->assertSessionHas('success', 'Reminded 1 student about the monthly evaluation.');
    expect(crNotes($f['sam'], 'Reminder: monthly evaluations to finish'))->toBe(0)->and(crNotes($f['hrUser'], 'Reminder: monthly evaluations to finish'))->toBe(0);
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly', 'audience' => 'supervisors'])
        ->assertSessionHas('success', 'Reminded 2 supervisors about the monthly evaluation.');
    expect(crNotes($f['mineUser'], 'Reminder: monthly evaluation'))->toBe(1);
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly'])->assertSessionHasErrors('audience'); // a group is required

    expect(crNotes($f['mineUser'], 'Reminder: monthly evaluation'))->toBe(1)
        ->and(crNotes($f['sam'], 'Reminder: monthly evaluations to finish'))->toBe(1) // two interns, one notification
        ->and(crNotes($f['hrUser'], 'Reminder: monthly evaluations to finish'))->toBe(1)
        ->and(crNotes($second->student->user))->toBe(0);
    $sam = AppNotification::where('user_id', $f['sam']->id)->sole();
    expect($sam->message)->toContain($second->student->user->name)->toContain($third->student->user->name)->toContain('due Mar 20');

    // Several interns: Sam lands on the Monthly Evaluations page. One intern: HR lands on that intern's tab.
    $this->actingAs($f['sam'])->get(route('notifications.show', $sam))->assertRedirect(route('supervisor.ojt.overview', 'monthly-evaluations'));
    $hr = AppNotification::where('user_id', $f['hrUser']->id)->sole();
    $this->actingAs($f['hrUser'])->get(route('notifications.show', $hr))
        ->assertRedirect(route('employer.interns.show', ['placement' => $f['free']->id, 'tab' => 'monthly-evaluation']));

    // No limit: again and again.
    $this->actingAs($f['coordinatorUser']);
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly', 'audience' => 'supervisors'])->assertSessionHas('success');
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly', 'audience' => 'students'])->assertSessionHas('success');
    expect(crNotes($f['sam'], 'Reminder: monthly evaluations to finish'))->toBe(2)->and(crNotes($f['mineUser'], 'Reminder: monthly evaluation'))->toBe(2);
});

it('reminds the supervisors about final evaluations that are requested and not submitted', function () {
    $f = crSetup();
    $f['mine']->update(['rating_requested_at' => now()->subDay(), 'rating_due_on' => '2026-03-20']);
    $done = crIntern($f);
    $done->update(['rating_requested_at' => now()->subDay(), 'rating_due_on' => '2026-03-20']);
    Evaluation::create(['placement_id' => $done->id, 'form_version' => 2, 'scores' => [], 'average_score' => 4, 'submitted_at' => now()]);
    $this->actingAs($f['coordinatorUser']);

    $this->post(route('coordinator.ojt.reminders'), ['type' => 'final', 'audience' => 'supervisors'])
        ->assertSessionHas('success', 'Reminded 1 supervisor about the final evaluation.');
    expect(crNotes($f['sam'], 'Reminder: final evaluations to finish'))->toBe(1);
    $notice = AppNotification::where('user_id', $f['sam']->id)->sole();
    $this->actingAs($f['sam'])->get(route('notifications.show', $notice))
        ->assertRedirect(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'evaluation']));

    // Nothing requested: nobody to remind.
    $f['mine']->update(['rating_requested_at' => null, 'rating_due_on' => null]);
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.ojt.reminders'), ['type' => 'final', 'audience' => 'supervisors'])->assertSessionHas('status', fn (string $m) => str_contains($m, 'Nobody to remind'));
});

it('reminds students about weekly reports not submitted and supervisors about reports waiting to be certified', function () {
    $f = crSetup();
    JournalEntry::create(['placement_id' => $f['mine']->id, 'week_start' => '2026-02-02', 'week_number' => 1, 'title' => 'Week 1', 'accomplishments' => 'x', 'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => JournalEntry::STATUS_SUBMITTED]);
    $this->actingAs($f['coordinatorUser']);

    $this->post(route('coordinator.ojt.reminders'), ['type' => 'weekly', 'audience' => 'students'])->assertSessionHas('success');
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'weekly', 'audience' => 'supervisors'])->assertSessionHas('success');

    $student = AppNotification::where('user_id', $f['mineUser']->id)->sole();
    expect($student->title)->toBe('Reminder: weekly report')->and($student->message)->toContain('Week 2 report')->toContain('overdue since');
    $sam = AppNotification::where('user_id', $f['sam']->id)->sole();
    expect($sam->message)->toContain($f['mineUser']->name)->toContain('1 weekly report waiting to be certified');
    // HR's intern has missing weeks only: the student is reminded, HR (covering) has nothing to certify.
    expect(crNotes($f['freeUser'], 'Reminder: weekly report'))->toBe(1)->and(crNotes($f['hrUser']))->toBe(0);
});

// ------------------------------------------------------------------ requests: once per period

it('requests the monthly evaluation once per month for every ongoing OJT, skipping the ones that have it', function () {
    $f = crSetup();
    crAppraisal($f['free']); // February is already requested for HR's intern
    $this->actingAs($f['coordinatorUser']);
    $due = now()->addWeek()->toDateString();

    $this->post(route('coordinator.ojt.requests'), ['type' => 'monthly', 'period_month' => '2026-02', 'due_on' => $due])
        ->assertSessionHas('success', fn (string $m) => str_contains($m, 'requested for 1 OJT(s)') && str_contains($m, 'Skipped 1') && str_contains($m, 'use Remind'));
    expect($f['mine']->appraisals()->count())->toBe(1)->and($f['free']->appraisals()->count())->toBe(1)
        ->and(crNotes($f['mineUser'], 'Monthly evaluation requested'))->toBe(1);
    $this->assertDatabaseHas('system_logs', ['user_id' => $f['coordinatorUser']->id, 'action' => 'coordinator_bulk_request']);

    // A second request for the same month changes nothing.
    $this->post(route('coordinator.ojt.requests'), ['type' => 'monthly', 'period_month' => '2026-02', 'due_on' => $due])
        ->assertSessionHas('success', fn (string $m) => str_contains($m, 'requested for 0 OJT(s)') && str_contains($m, 'Skipped 2'));
    expect(Appraisal::count())->toBe(2);

    // The month and the due date are checked.
    $this->post(route('coordinator.ojt.requests'), ['type' => 'monthly', 'due_on' => $due])->assertSessionHasErrors('period_month');
    $this->post(route('coordinator.ojt.requests'), ['type' => 'monthly', 'period_month' => '2026-03', 'due_on' => now()->subDay()->toDateString()])->assertSessionHasErrors('due_on');
    $this->post(route('coordinator.ojt.requests'), ['type' => 'weekly', 'due_on' => $due])->assertSessionHasErrors('type');
});

it('requests the final evaluation once for every ongoing OJT, notifying the supervisors, and skips requested or evaluated ones', function () {
    $f = crSetup();
    $evaluated = crIntern($f);
    Evaluation::create(['placement_id' => $evaluated->id, 'form_version' => 2, 'scores' => [], 'average_score' => 4, 'submitted_at' => now()]);
    $this->actingAs($f['coordinatorUser']);
    $due = now()->addDays(10)->toDateString();

    $this->post(route('coordinator.ojt.requests'), ['type' => 'final', 'due_on' => $due])
        ->assertSessionHas('success', fn (string $m) => str_contains($m, 'Final evaluation requested for 2 OJT(s)') && str_contains($m, 'Skipped 1'));
    expect($f['mine']->fresh()->rating_requested_at)->not->toBeNull()->and($f['mine']->fresh()->rating_due_on->toDateString())->toBe($due)
        ->and($f['free']->fresh()->rating_requested_at)->not->toBeNull()
        ->and($evaluated->fresh()->rating_requested_at)->toBeNull()
        // Sam gets his intern's request; HR gets the one nobody supervises.
        ->and(crNotes($f['sam'], 'Rating requested'))->toBe(1)->and(crNotes($f['hrUser'], 'Rating requested'))->toBe(1);

    // Once only: asking again requests nothing and sends nothing.
    $this->post(route('coordinator.ojt.requests'), ['type' => 'final', 'due_on' => $due])
        ->assertSessionHas('success', fn (string $m) => str_contains($m, 'requested for 0 OJT(s)') && str_contains($m, 'Skipped 3'));
    expect(crNotes($f['sam'], 'Rating requested'))->toBe(1);

    // The single request on a student's page still works (it changes the due date).
    $this->post(route('coordinator.placements.request-rating', $f['mine']), ['rating_due_on' => now()->addDays(3)->toDateString()])->assertSessionHas('success');
    expect($f['mine']->fresh()->rating_due_on->toDateString())->toBe(now()->addDays(3)->toDateString());
});

// ------------------------------------------------------------------ the page

it('shows the requests and reminders panel and the Remind to log time button on OJT Monitoring', function () {
    $f = crSetup();
    $logged = crIntern($f);
    crLogToday($logged);

    $page = $this->actingAs($f['coordinatorUser'])->get(route('coordinator.placements.index'))->assertOk();
    $page->assertSee('Requests and reminders')
        ->assertSee('Daily time record')->assertSee('Weekly report')->assertSee('Monthly evaluation')->assertSee('Final evaluation')
        ->assertSee('Students with no time logged today: 2')
        ->assertSee('Remind students')->assertSee('Remind supervisors')->assertSee('Request monthly evaluation')->assertSee('Request final evaluation')
        ->assertSee(route('coordinator.ojt.requests'), false)->assertSee(route('coordinator.ojt.reminders'), false)
        ->assertSee(route('coordinator.placements.remind-daily-log', $f['mine']), false)->assertSee(route('coordinator.placements.remind-daily-log', $f['free']), false)
        ->assertDontSee(route('coordinator.placements.remind-daily-log', $logged), false);

    // After a reminder the row says when it can go out again, and the panel counts the student as waiting.
    $this->post(route('coordinator.placements.remind-daily-log', $f['mine']));
    $this->get(route('coordinator.placements.index'))->assertOk()
        ->assertSee('Again 5:00 PM')->assertDontSee(route('coordinator.placements.remind-daily-log', $f['mine']), false)
        ->assertSee(route('coordinator.placements.remind-daily-log', $f['free']), false)
        ->assertSee('1 reminded in the last 8 hours');

    // The admin's page is unchanged.
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get(route('admin.placements.index'))->assertOk()->assertDontSee('Remind to log time');
});

it('keeps requests and reminders to coordinators', function () {
    $f = crSetup();
    $routes = [
        fn () => $this->post(route('coordinator.ojt.requests'), ['type' => 'final', 'due_on' => now()->addWeek()->toDateString()]),
        fn () => $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'students']),
        fn () => $this->post(route('coordinator.placements.remind-daily-log', $f['mine'])),
    ];

    foreach ([$f['mineUser'], $f['sam'], $f['hrUser'], User::factory()->create(['role' => UserRole::Admin])] as $user) {
        $this->actingAs($user);
        foreach ($routes as $call) {
            $call()->assertForbidden();
        }
    }
    auth()->logout();
    foreach ($routes as $call) {
        $call()->assertRedirect(route('login'));
    }
    expect(crNotes($f['mineUser']))->toBe(0);
});

it('sends Back to the page the student was opened from, and remembers it', function () {
    $f = crSetup();
    $placement = $f['mine'];
    $show = route('coordinator.placements.show', $placement);

    $this->actingAs($f['coordinatorUser'])->get($show, ['Referer' => url('/coordinator/reviews?tab=evaluations&type=monthly&status=waiting')])
        ->assertOk()->assertSee('Back to Evaluation Reviews')->assertSee('/coordinator/reviews?tab=evaluations&amp;type=monthly&amp;status=waiting', false);
    // After an action on the page the referer is the page itself: the remembered place stays.
    $this->get($show, ['Referer' => $show])->assertSee('Back to Evaluation Reviews');
    // Another site is never used.
    $this->get(route('coordinator.placements.show', $placement), ['Referer' => 'https://evil.example/coordinator/x'])->assertSee('Back to Evaluation Reviews');
});
