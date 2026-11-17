<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Mail\SystemNotificationMail;
use App\Models\AppNotification;
use App\Models\Appraisal;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Services\AppraisalService;
use App\Services\OjtDeadlineService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['ojt_reminders.weekly_reports_from' => '2026-01-01']);
});

/** An OJT starting Monday, March 2, 2026 (week 1 ends Friday, March 6; its report is due March 9). */
function remPlacement(string $status = Placement::STATUS_ACTIVE, string $start = '2026-03-02'): array
{
    $institution = Institution::create(['name' => 'Reminder University '.uniqid()]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Remind Co '.uniqid(), 'contact_person' => 'Sup']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program' => 'BSIT']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    assignDepartment(Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]), $student);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Dev Intern', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 70]);
    $placement = Placement::create(['application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id,
        'internship_id' => $internship->id, 'start_date' => $start, 'required_hours' => 500, 'status' => $status]);

    return compact('placement', 'studentUser', 'employerUser', 'coordinatorUser');
}

function remAt(string $manilaDateTime): void
{
    test()->travelTo(CarbonImmutable::parse($manilaDateTime, 'Asia/Manila'));
}

function remRun(): void
{
    test()->artisan('ojt:remind-documents')->assertSuccessful();
}

function remTitles(User $user): array
{
    return AppNotification::where('user_id', $user->id)->where('type', 'ojt_reminder')->orderBy('id')->pluck('title')->all();
}

it('reminds the student about a missing weekly report once before it is due and once when overdue', function () {
    $f = remPlacement();

    remAt('2026-03-07 08:00'); // week 1 ended, due March 9: not inside the 1-day window yet
    remRun();
    expect(remTitles($f['studentUser']))->toBe([]);

    remAt('2026-03-08 08:00'); // one day before due
    remRun();
    remRun(); // never twice
    expect(remTitles($f['studentUser']))->toBe(['Weekly report due soon']);

    remAt('2026-03-10 08:00'); // past due
    remRun();
    remRun();
    expect(remTitles($f['studentUser']))->toBe(['Weekly report due soon', 'Weekly report overdue'])
        ->and(remTitles($f['employerUser']))->toBe([]); // only the student owes weekly reports
    expect(AppNotification::where('user_id', $f['studentUser']->id)->where('type', 'ojt_reminder')->latest('id')->first()->message)
        ->toContain('Week 1 report')->toContain('Mar 09, 2026');

    // Once submitted, the week is no longer owed.
    JournalEntry::create(['placement_id' => $f['placement']->id, 'week_start' => '2026-03-09', 'week_number' => 2, 'title' => 'Week 2',
        'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => 'submitted']);
    remAt('2026-03-16 08:00'); // week 2 is due March 16: inside its window, but already submitted
    remRun();
    expect(remTitles($f['studentUser']))->toHaveCount(2);
});

it('reminds whoever\'s turn it is on a monthly evaluation', function () {
    remAt('2026-03-10 09:00');
    $f = remPlacement();
    JournalEntry::create(['placement_id' => $f['placement']->id, 'week_start' => '2026-03-02', 'week_number' => 1, 'title' => 'Week 1',
        'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => 'submitted']); // keep weekly reminders out of the way
    config(['ojt_reminders.weekly_reports_from' => '2026-12-31']);
    $service = app(AppraisalService::class);
    $appraisal = $service->request($f['placement'], $f['coordinatorUser'], CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-03-20'));

    remAt('2026-03-17 08:00'); // three days before: nothing yet
    remRun();
    expect(remTitles($f['studentUser']))->toBe([]);

    remAt('2026-03-18 08:00'); // two days before: the student rates first
    remRun();
    expect(remTitles($f['studentUser']))->toBe(['Monthly evaluation due soon'])->and(remTitles($f['employerUser']))->toBe([]);

    $service->submitSelf($appraisal, $f['studentUser'], collect(Appraisal::FACTORS)->map(fn ($x) => $x['points'])->all());
    remAt('2026-03-19 08:00'); // now the supervisor's turn
    remRun();
    expect(remTitles($f['employerUser']))->toBe(['Monthly evaluation due soon']);

    $service->submitSupervisor($appraisal, $f['employerUser'], collect(Appraisal::FACTORS)->map(fn ($x) => $x['points'])->all(), 'Good.');
    remAt('2026-03-21 08:00'); // the student's comment is now overdue
    remRun();
    remRun();
    expect(remTitles($f['studentUser']))->toBe(['Monthly evaluation due soon', 'Monthly evaluation comment overdue'])
        ->and(remTitles($f['employerUser']))->toBe(['Monthly evaluation due soon']);
});

it('reminds the student again after the coordinator reopens their rating', function () {
    remAt('2026-03-10 09:00');
    config(['ojt_reminders.weekly_reports_from' => '2026-12-31']);
    $f = remPlacement();
    $service = app(AppraisalService::class);
    $appraisal = $service->request($f['placement'], $f['coordinatorUser'], CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-03-20'));

    remAt('2026-03-19 08:00');
    remRun();
    $service->submitSelf($appraisal, $f['studentUser'], collect(Appraisal::FACTORS)->map(fn ($x) => 5)->all());
    $service->reopen($appraisal, $f['coordinatorUser']);
    remRun();

    expect(remTitles($f['studentUser']))->toBe(['Monthly evaluation due soon', 'Monthly evaluation due soon']);
});

it('reminds the supervisor about the final evaluation until it is submitted', function () {
    remAt('2026-03-10 09:00');
    config(['ojt_reminders.weekly_reports_from' => '2026-12-31']);
    $f = remPlacement();
    $f['placement']->update(['rating_requested_at' => now(), 'rating_due_on' => '2026-03-20']);

    remAt('2026-03-18 08:00');
    remRun();
    remAt('2026-03-22 08:00');
    remRun();
    remRun();
    expect(remTitles($f['employerUser']))->toBe(['Final evaluation due soon', 'Final evaluation overdue']);

    // A new request with a new due date can be reminded again; a submitted evaluation stops it.
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.placements.request-rating', $f['placement']), ['rating_due_on' => '2026-03-24'])->assertSessionHas('success');
    remRun();
    expect(remTitles($f['employerUser']))->toHaveCount(3);
    Evaluation::create(['placement_id' => $f['placement']->id, 'form_version' => 2, 'scores' => [], 'average_score' => 5, 'submitted_at' => now()]);
    remAt('2026-03-26 08:00');
    remRun();
    expect(remTitles($f['employerUser']))->toHaveCount(3);
});

it('sends the email copy only to users who keep email notifications on', function () {
    $on = remPlacement();
    $off = remPlacement();
    $off['studentUser']->update(['email_notifications' => false]);

    remAt('2026-03-10 08:00');
    remRun();

    expect(remTitles($on['studentUser']))->toBe(['Weekly report overdue'])
        ->and(remTitles($off['studentUser']))->toBe(['Weekly report overdue']); // the bell still shows it
    Mail::assertSent(SystemNotificationMail::class, fn ($mail) => $mail->hasTo($on['studentUser']->email));
    Mail::assertNotSent(SystemNotificationMail::class, fn ($mail) => $mail->hasTo($off['studentUser']->email));
});

it('skips placements that are not active, and weeks before reminders started', function () {
    $pending = remPlacement(Placement::STATUS_PENDING_VALIDATION);
    $completed = remPlacement(Placement::STATUS_COMPLETED);
    $completed['placement']->update(['rating_requested_at' => now(), 'rating_due_on' => '2026-03-11']);
    $old = remPlacement();
    config(['ojt_reminders.weekly_reports_from' => '2026-03-07']); // week 1 (ended March 6) is before the feature

    remAt('2026-03-10 08:00');
    remRun();

    expect(remTitles($pending['studentUser']))->toBe([])
        ->and(remTitles($completed['studentUser']))->toBe([])
        ->and(remTitles($completed['employerUser']))->toBe([])
        ->and(remTitles($old['studentUser']))->toBe([]);
});

it('runs daily at 08:00 Philippine time and counts days in Philippine time', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'ojt:remind-documents'));
    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 8 * * *')
        ->and((string) $event->timezone)->toBe('Asia/Manila');

    // 17:30 UTC on March 8 is already March 9 in Manila.
    $this->travelTo(CarbonImmutable::parse('2026-03-08 17:30:00', 'UTC'));
    expect(OjtDeadlineService::today()->toDateString())->toBe('2026-03-09');
});

it('shows Due and Overdue badges to the student, the supervisor and the coordinator', function () {
    $f = remPlacement();
    $f['placement']->update(['rating_requested_at' => now(), 'rating_due_on' => '2026-03-20']);
    remAt('2026-03-10 09:00');

    $this->actingAs($f['studentUser'])->get(route('student.ojt.index'))->assertOk()
        ->assertSee('Due documents')->assertSee('Week 1 report')->assertSee('Overdue · was due Mar 9')
        ->assertDontSee('Final performance evaluation');
    $this->actingAs($f['employerUser'])->get(route('employer.interns.show', $f['placement']))->assertOk()
        ->assertSee('Final performance evaluation')->assertSee('Due Mar 20')->assertDontSee('Week 1 report');
    $this->actingAs($f['coordinatorUser'])->get(route('coordinator.placements.index'))->assertOk()
        ->assertSee('1 overdue');
});
