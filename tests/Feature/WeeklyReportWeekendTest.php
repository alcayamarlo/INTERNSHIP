<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AttendanceLog;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Services\WeeklyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/*
 * Weekly report days: Monday to Friday, plus a Saturday or Sunday only when the student logged time
 * that day, and never a day before the OJT start date (or after its end date). A week runs Monday to
 * Sunday, so a Sunday belongs to the week that started the Monday before it.
 */

beforeEach(fn () => Mail::fake());

function weekendFixture(string $start, ?string $end = null): array
{
    $institution = Institution::create(['name' => 'Weekend University '.uniqid()]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Weekend Labs', 'contact_person' => 'Maria L. Santos']);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Wendy Weekend', 'phone' => '09171234567']);
    $student = Student::create([
        'user_id' => $studentUser->id, 'institution_id' => $institution->id, 'first_name' => 'Wendy', 'last_name' => 'Weekend',
        'program' => 'BS Information Technology', 'year_level' => '4th Year', 'student_id_number' => sprintf('SCC-21-%08d', random_int(1, 99999999)),
    ]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Weekend Trainee', 'description' => 'Work.', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id, 'internship_id' => $internship->id,
        'start_date' => $start, 'end_date' => $end, 'required_hours' => 486, 'status' => Placement::STATUS_ACTIVE, 'department' => 'IT Department',
    ]);

    return compact('placement', 'studentUser', 'employerUser');
}

function weekendLog(Placement $placement, string $date, float $hours, string $task, string $status = AttendanceLog::STATUS_APPROVED): void
{
    $placement->attendanceLogs()->create([
        'work_date' => $date, 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60,
        'hours' => $hours, 'task_summary' => $task, 'status' => $status,
    ]);
}

function weekendReport(string $weekDay, array $overrides = []): array
{
    return [
        'week_start' => $weekDay,
        'challenges' => 'Getting used to the project structure this week.',
        'actions_taken' => 'I read the documentation and asked my supervisor.',
        'learnings' => 'How the team reviews and merges code changes.',
        ...$overrides,
    ];
}

it('covers weekdays, logged weekend days and OJT dates only', function () {
    $days = fn (string $day, ?string $start, ?string $end, array $weekend) => array_map(
        fn ($date) => $date->format('D d'),
        Placement::coveredDays(CarbonImmutable::parse($day), $start ? CarbonImmutable::parse($start) : null, $end ? CarbonImmutable::parse($end) : null, $weekend),
    );

    // No weekend hours: the usual Monday to Friday.
    expect($days('2026-04-22', '2026-01-28', null, []))->toBe(['Mon 20', 'Tue 21', 'Wed 22', 'Thu 23', 'Fri 24'])
        // A logged Saturday is added; an unlogged Sunday is not.
        ->and($days('2026-04-22', '2026-01-28', null, ['2026-04-25']))->toBe(['Mon 20', 'Tue 21', 'Wed 22', 'Thu 23', 'Fri 24', 'Sat 25'])
        // A Sunday belongs to the week of the Monday before it.
        ->and($days('2026-04-26', '2026-01-28', null, ['2026-04-26']))->toBe(['Mon 20', 'Tue 21', 'Wed 22', 'Thu 23', 'Fri 24', 'Sun 26'])
        // Never before the start date or after the end date.
        ->and($days('2026-01-28', '2026-01-28', null, []))->toBe(['Wed 28', 'Thu 29', 'Fri 30'])
        ->and($days('2026-11-15', '2026-11-15', null, ['2026-11-15']))->toBe(['Sun 15'])
        ->and($days('2026-04-20', '2026-01-28', '2026-04-22', ['2026-04-25']))->toBe(['Mon 20', 'Tue 21', 'Wed 22']);
});

it('reports a Sunday start in the week it belongs to, with only the OJT days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-11-15'); // a Sunday
    weekendLog($f['placement'], '2026-11-15', 8, 'Set up the development environment');

    $this->actingAs($f['studentUser'])
        ->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-11-15'))
        ->assertSessionHas('success');

    $entry = $f['placement']->journalEntries()->sole();
    $rows = app(WeeklyReportService::class)->dailyRows($entry);
    expect($entry->week_start->toDateString())->toBe('2026-11-09')
        ->and($entry->week_number)->toBe(1)
        ->and(array_column($rows, 'date'))->toBe(['2026-11-15'])
        ->and($rows[0]['day'])->toBe('Sun')
        ->and($rows[0]['activity'])->toBe('Set up the development environment')
        ->and($rows[0]['hours_label'])->toBe('8 hrs')
        ->and($entry->header_snapshot['inclusive_dates'])->toBe('November 15, 2026')
        ->and($entry->inclusiveDates())->toBe('November 15, 2026');
});

it('starts the first week on the OJT start date, not on the Monday before it', function () {
    $this->travelTo(CarbonImmutable::parse('2026-02-02 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28'); // a Wednesday
    weekendLog($f['placement'], '2026-01-28', 8, 'Orientation');

    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-01-28'))->assertSessionHas('success');

    $entry = $f['placement']->journalEntries()->sole();
    expect(array_column(app(WeeklyReportService::class)->dailyRows($entry), 'day'))->toBe(['Wed', 'Thu', 'Fri'])
        ->and($entry->header_snapshot['inclusive_dates'])->toBe('January 28 - 30, 2026');
});

it('prints a weekend row only when time was logged that day, and keeps it after certifying', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-27 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28');
    weekendLog($f['placement'], '2026-04-20', 8, 'Monday work');
    weekendLog($f['placement'], '2026-04-25', 4, 'Saturday deployment');
    weekendLog($f['placement'], '2026-04-26', 3, 'Rejected Sunday', AttendanceLog::STATUS_REJECTED);

    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-04-22'))->assertSessionHas('success');
    $entry = $f['placement']->journalEntries()->sole();
    $service = app(WeeklyReportService::class);

    expect(array_column($service->dailyRows($entry), 'day'))->toBe(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
        ->and($service->dailyRows($entry)[5]['activity'])->toBe('Saturday deployment')
        ->and($entry->header_snapshot['inclusive_dates'])->toBe('April 20 - 25, 2026');

    $service->certify($entry, $f['employerUser'], null);
    $certified = $entry->fresh();
    expect(array_column($certified->daily_snapshot, 'day'))->toBe(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
        ->and(count($service->printData($certified)['rows']))->toBe(6);

    $this->get(route('ojt.journals.print', $certified))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('keeps the usual Monday-to-Friday form when there are no weekend hours', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-27 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28');
    weekendLog($f['placement'], '2026-04-21', 8, 'Tuesday work');

    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-04-26'))->assertSessionHas('success');
    $entry = $f['placement']->journalEntries()->sole();

    expect($entry->week_start->toDateString())->toBe('2026-04-20')
        ->and(array_column(app(WeeklyReportService::class)->dailyRows($entry), 'day'))->toBe(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
        ->and($entry->header_snapshot['inclusive_dates'])->toBe('April 20 - 24, 2026');
});

it('stops at the OJT end date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-27 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28', '2026-04-22');

    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-04-20'))->assertSessionHas('success');
    $entry = $f['placement']->journalEntries()->sole();

    expect(array_column(app(WeeklyReportService::class)->dailyRows($entry), 'day'))->toBe(['Mon', 'Tue', 'Wed'])
        ->and($entry->header_snapshot['inclusive_dates'])->toBe('April 20 - 22, 2026');
});

it('accepts typed inclusive dates up to Sunday but never before the OJT start', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-27 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28');
    $this->actingAs($f['studentUser']);

    $this->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-01-28', ['header' => ['inclusive_dates' => 'January 26 - 30, 2026']]))
        ->assertSessionHasErrors(['header.inclusive_dates' => 'Inclusive dates must be days of that week (Monday to Sunday) within your OJT, e.g. "April 20 - 24, 2026".']);
    $this->post(route('student.ojt.journals.store', $f['placement']), weekendReport('2026-04-20', ['header' => ['inclusive_dates' => 'April 20 - 25, 2026']]))
        ->assertSessionHas('success');

    expect(JournalEntry::sole()->header_snapshot['inclusive_dates'])->toBe('April 20 - 25, 2026');
});

it('gives the report form the OJT dates and logged weekend days for its Inclusive Dates preview', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-27 10:00:00', 'Asia/Manila'));
    $f = weekendFixture('2026-01-28');
    weekendLog($f['placement'], '2026-04-25', 4, 'Saturday deployment');
    weekendLog($f['placement'], '2026-04-22', 8, 'Wednesday work');

    $this->actingAs($f['studentUser'])->get(route('student.ojt.index'))
        ->assertOk()
        ->assertSee('data-start-date="2026-01-28"', false)
        ->assertSee('data-logged-weekend=\'["2026-04-25"]\'', false);
});
