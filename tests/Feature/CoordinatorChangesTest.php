<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AttendanceLog;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ccSetup(): array
{
    $institution = Institution::create(['name' => 'SCC']);
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id]);
    $bsit = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'required_internship_hours' => 486]);
    $bsba = Program::create(['name' => 'BS Business Administration', 'code' => 'BSBA', 'required_internship_hours' => 486]);
    $coordinator->update(['program_id' => $bsit->id]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme Corp', 'contact_person' => 'Grace HR', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'status' => 'open']);

    return compact('institution', 'user', 'coordinator', 'bsit', 'bsba', 'employer', 'internship');
}

function ccStudent(array $c, Program $program, string $name): Student
{
    return Student::create([
        'user_id' => User::factory()->create(['role' => UserRole::Student, 'name' => $name])->id,
        'institution_id' => $c['institution']->id, 'program_id' => $program->id, 'program' => $program->code,
        'year_level' => '4th Year', 'major_subjects_passed' => true,
    ]);
}

function ccPlacement(array $c, Student $student): Placement
{
    $application = InternshipApplication::create(['internship_id' => $c['internship']->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);
    $placement = app(PlacementService::class)->openForApplication($application);
    $placement->update(['status' => Placement::STATUS_ACTIVE, 'start_date' => now()->subDays(60)->toDateString()]);

    return $placement->fresh();
}

it('locks the student list Program filter to the coordinator\'s own program', function () {
    $c = ccSetup();
    $mine = ccStudent($c, $c['bsit'], 'Ana Cruz');
    $other = ccStudent($c, $c['bsba'], 'Ben Reyes');
    $this->actingAs($c['user']);

    $this->get(route('coordinator.students.index'))->assertOk()
        ->assertDontSee('All programs')
        ->assertSee('id="studentProgram" class="form-select" disabled', false)
        ->assertSee('BS Information Technology');
    // Asking for another program changes nothing: only the coordinator's own students come back.
    $this->getJson(route('coordinator.students.index', ['program' => 'BSBA']))
        ->assertOk()->assertJsonCount(1, 'students')->assertJsonFragment(['id' => $mine->id])->assertJsonMissing(['id' => $other->id]);
});

it('calls the page Evaluation Reviews and lists Daily Logs, with every log and not only the last 14 days', function () {
    $c = ccSetup();
    $placement = ccPlacement($c, ccStudent($c, $c['bsit'], 'Ana Cruz'));
    foreach ([2, 40] as $daysAgo) {
        AttendanceLog::create(['placement_id' => $placement->id, 'work_date' => now()->subDays($daysAgo)->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'hours' => 8, 'task_summary' => 'Coding', 'status' => AttendanceLog::STATUS_PENDING]);
    }
    $this->actingAs($c['user']);

    $this->get(route('coordinator.reviews.index'))->assertOk()
        ->assertSee('Evaluation Reviews')->assertSee('Daily logs')
        ->assertDontSee('Evaluation Reports')->assertDontSee('Report Reviews')->assertDontSee('Attendance (last 14 days)');
    $html = $this->get(route('coordinator.reviews.index', ['tab' => 'logs']))->assertOk()->getContent();
    expect(substr_count($html, 'Ana Cruz</a>'))->toBe(2); // both logs, the 40-day-old one too
    $this->get(route('coordinator.reviews.index', ['tab' => 'logs', 'from' => now()->subDays(5)->toDateString()]))->assertOk()->assertDontSee(now()->subDays(40)->format('M d, Y'));
    $this->get(route('coordinator.reviews.index', ['tab' => 'attendance']))->assertOk()->assertSee('Daily logs'); // an old link still works
});

it('keeps reviewed, returned and waiting-for-supervisor weekly reports reachable, read-only', function () {
    $c = ccSetup();
    $placement = ccPlacement($c, ccStudent($c, $c['bsit'], 'Ana Cruz'));
    $week = now()->startOfWeek();
    JournalEntry::create(['placement_id' => $placement->id, 'week_start' => $week->toDateString(), 'title' => 'Waiting report', 'accomplishments' => 'a', 'status' => JournalEntry::STATUS_ACKNOWLEDGED]);
    JournalEntry::create(['placement_id' => $placement->id, 'week_start' => $week->copy()->subWeek()->toDateString(), 'title' => 'Done report', 'accomplishments' => 'a', 'status' => JournalEntry::STATUS_ACKNOWLEDGED, 'coordinator_review_status' => 'reviewed', 'coordinator_reviewed_at' => now()]);
    JournalEntry::create(['placement_id' => $placement->id, 'week_start' => $week->copy()->subWeeks(2)->toDateString(), 'title' => 'Sent back report', 'accomplishments' => 'a', 'status' => JournalEntry::STATUS_ACKNOWLEDGED, 'coordinator_review_status' => 'returned', 'coordinator_comment' => 'Add tasks', 'coordinator_reviewed_at' => now()]);
    JournalEntry::create(['placement_id' => $placement->id, 'week_start' => $week->copy()->subWeeks(3)->toDateString(), 'title' => 'Supervisor report', 'accomplishments' => 'a', 'status' => JournalEntry::STATUS_SUBMITTED]);
    $this->actingAs($c['user']);

    $this->get(route('coordinator.reviews.index'))->assertSee('Waiting report')->assertSee('Mark reviewed')->assertDontSee('Done report')->assertDontSee('Sent back report')->assertDontSee('Supervisor report');
    $this->get(route('coordinator.reviews.index', ['show' => 'reviewed']))->assertSee('Done report')->assertSee('Reviewed')->assertDontSee('Mark reviewed');
    $this->get(route('coordinator.reviews.index', ['show' => 'returned']))->assertSee('Sent back report')->assertSee('Add tasks')->assertDontSee('Mark reviewed');
    $this->get(route('coordinator.reviews.index', ['show' => 'supervisor']))->assertSee('Supervisor report')->assertSee('Waiting for supervisor')->assertDontSee('Mark reviewed');
    $this->get(route('coordinator.reviews.index', ['show' => 'all']))->assertSee('Waiting report')->assertSee('Done report')->assertSee('Supervisor report');
});

it('lists final evaluations that were never requested too', function () {
    $c = ccSetup();
    $placement = ccPlacement($c, ccStudent($c, $c['bsit'], 'Ana Cruz'));
    expect($placement->rating_requested_at)->toBeNull();
    $this->actingAs($c['user']);

    $this->get(route('coordinator.reviews.index', ['tab' => 'evaluations', 'type' => 'final']))->assertOk()->assertSee('Ana Cruz');
});

it('sends Back to Evaluation Reviews with its tab and filters, from an explicit link', function () {
    $c = ccSetup();
    $placement = ccPlacement($c, ccStudent($c, $c['bsit'], 'Ana Cruz'));
    $this->actingAs($c['user']);
    $reviews = '/coordinator/reviews?tab=evaluations&type=monthly&status=waiting_coordinator&page=2';

    $this->get(route('coordinator.placements.show', ['placement' => $placement, 'back' => $reviews]))->assertOk()
        ->assertSee('Back to Evaluation Reviews')->assertSee(e($reviews), false);
    // Stays after the page's own actions (a reload without the parameter).
    $this->get(route('coordinator.placements.show', $placement))->assertSee(e($reviews), false);

    // Only coordinator pages of this site are accepted.
    $fresh = ccPlacement($c, ccStudent($c, $c['bsit'], 'Cy Dela Cruz'));
    $this->get(route('coordinator.placements.show', ['placement' => $fresh, 'back' => 'https://evil.example/coordinator/x']))->assertOk()->assertDontSee('evil.example');
    $this->get(route('coordinator.placements.show', ['placement' => $fresh, 'back' => '//evil.example/coordinator/x']))->assertOk()->assertDontSee('evil.example');
});

it('reminds only the students or only the supervisors, depending on the button', function () {
    $c = ccSetup();
    $this->actingAs($c['user']);

    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly'])->assertSessionHasErrors('audience');
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'monthly', 'audience' => 'everyone'])->assertSessionHasErrors('audience');
    $this->post(route('coordinator.ojt.reminders'), ['type' => 'daily', 'audience' => 'supervisors'])->assertSessionHas('status', fn (string $m) => str_contains($m, 'no supervisor reminder'));
});

it('no longer shows OJT by program on Statistics and Reports', function () {
    $c = ccSetup();
    ccStudent($c, $c['bsit'], 'Ana Cruz');
    $this->actingAs($c['user']);

    $this->get(route('coordinator.statistics.index'))->assertOk()->assertDontSee('By program')->assertSee('Partner companies');
    expect(app(\App\Services\InstitutionStatistics::class)->for($c['coordinator']))->not->toHaveKey('programs');
});
