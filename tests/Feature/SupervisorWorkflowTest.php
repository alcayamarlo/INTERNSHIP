<?php

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Placement;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\EvaluationProgress;
use App\Services\OjtDocumentData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A company with HR, a supervisor (Sam, QA Lead), an intern assigned to Sam, an intern with nobody, and a coordinator for both. */
function swSetup(): array
{
    $company = svCompany('Workflow Co');
    $sam = svSupervisor($company['employer'], 'Sam Supervisor');
    $sam->supervisor->update(['position' => 'QA Lead']);
    $mine = svPlacement($company['employer'], $sam);
    $free = svPlacement($company['employer']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    assignDepartment(Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => Institution::first()->id]), $mine->student, $free->student);

    return [...$company, 'sam' => $sam, 'mine' => $mine, 'free' => $free, 'coordinatorUser' => $coordinatorUser];
}

function swNotices(User $user, string $title): int
{
    return AppNotification::where('user_id', $user->id)->where('title', $title)->count();
}

it('sends the student\'s daily time notice to the assigned supervisor, not to HR', function () {
    $f = swSetup();
    $log = fn (Placement $placement) => $this->actingAs($placement->student->user)->post(route('student.ojt.attendance.store', $placement), [
        'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'task_summary' => 'Worked on tickets.',
    ])->assertSessionHasNoErrors();

    $log($f['mine']);
    expect(swNotices($f['sam'], 'New DTR entry'))->toBe(1)->and(swNotices($f['hrUser'], 'New DTR entry'))->toBe(0);

    // Nobody assigned: HR gets it, as before.
    $log($f['free']);
    expect(swNotices($f['hrUser'], 'New DTR entry'))->toBe(1);

    // The assigned supervisor was deactivated: HR covers the intern again.
    $f['sam']->update(['is_active' => false]);
    $this->actingAs($f['mine']->student->user)->post(route('student.ojt.attendance.store', $f['mine']), [
        'work_date' => now()->subDays(2)->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'task_summary' => 'More tickets.',
    ]);
    expect(swNotices($f['hrUser'], 'New DTR entry'))->toBe(2)->and(swNotices($f['sam'], 'New DTR entry'))->toBe(1);
});

it('asks the supervisor, not HR, for the final evaluation and the monthly rating', function () {
    $f = swSetup();
    $this->actingAs($f['coordinatorUser'])->post(route('coordinator.placements.request-rating', $f['mine']))->assertSessionHas('success');
    expect(swNotices($f['sam'], 'Rating requested'))->toBe(1)->and(swNotices($f['hrUser'], 'Rating requested'))->toBe(0);
    $this->post(route('coordinator.placements.request-rating', $f['free']))->assertSessionHas('success');
    expect(swNotices($f['hrUser'], 'Rating requested'))->toBe(1);

    // The coordinator's "Remind" on a monthly evaluation waiting for the supervisor goes to the supervisor.
    $appraisal = Appraisal::create([
        'placement_id' => $f['mine']->id, 'period_month' => '2026-03-01', 'period_label' => 'Midterm', 'period_start' => '2026-03-01',
        'period_end' => '2026-03-28', 'due_on' => now()->addWeek()->toDateString(), 'self_submitted_at' => now(),
    ]);
    expect(app(EvaluationProgress::class)->remind('monthly', $appraisal->id, $f['coordinatorUser']->coordinator))->toBe('Sam Supervisor')
        ->and(swNotices($f['sam'], 'Reminder: monthly evaluation'))->toBe(1)->and(swNotices($f['hrUser'], 'Reminder: monthly evaluation'))->toBe(0);

    // The supervisor's notification opens their own intern page.
    $notice = AppNotification::where('user_id', $f['sam']->id)->where('title', 'Rating requested')->sole();
    $this->actingAs($f['sam'])->get(route('notifications.show', $notice))
        ->assertRedirect(route('supervisor.interns.show', ['placement' => $f['mine']->id, 'tab' => 'evaluation']));
});

it('prints the assigned supervisor\'s name and job title on the forms, and keeps what was signed', function () {
    $f = swSetup();
    $docs = app(OjtDocumentData::class);
    $values = $docs->values($f['mine']->fresh());
    expect($values['supervisor_name'])->toBe('Sam Supervisor')->and($values['supervisor_title'])->toBe('QA Lead')
        ->and(OjtDocumentData::SNAPSHOT_FIELDS)->toContain('supervisor_title');

    // An unassigned intern prints the company contact and the HR account's title.
    $f['employer']->update(['position' => 'HR Manager']);
    $free = $docs->values($f['free']->fresh());
    expect($free['supervisor_name'])->toBe('Workflow Co HR')->and($free['supervisor_title'])->toBe('HR Manager');

    // A frozen form keeps the supervisor it was signed with, even if the intern gets another one later.
    $frozen = OjtDocumentData::withSnapshot($docs->values($f['mine']->fresh()), collect($values)->only(OjtDocumentData::SNAPSHOT_FIELDS)->all());
    $kim = svSupervisor($f['employer'], 'Kim Newsupervisor');
    $f['mine']->update(['supervisor_user_id' => $kim->id]);
    expect($docs->values($f['mine']->fresh())['supervisor_name'])->toBe('Kim Newsupervisor')->and($frozen['supervisor_name'])->toBe('Sam Supervisor');

    // Deactivated supervisor: HR's contact is printed (HR covers), not the stale name.
    $kim->update(['is_active' => false]);
    expect($docs->values($f['mine']->fresh())['supervisor_name'])->toBe('Workflow Co HR');
});

it('names the supervisor assigned to the student on a report that is not certified yet, and keeps the certifier once it is', function () {
    $f = swSetup();
    $service = app(\App\Services\WeeklyReportService::class);
    $placement = $f['free']; // no supervisor yet: HR covers
    $submittedBeforeAssignment = ['supervisor_name' => 'Workflow Co HR', 'supervisor_title' => 'HR Manager'];
    $entry = \App\Models\JournalEntry::create([
        'placement_id' => $placement->id, 'week_start' => '2026-02-09', 'week_number' => 2, 'title' => 'Week 2', 'accomplishments' => 'x',
        'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => \App\Models\JournalEntry::STATUS_SUBMITTED, 'header_snapshot' => $submittedBeforeAssignment,
    ]);
    expect($service->printData($entry)['header']['supervisor_name'])->toBe('Workflow Co HR');

    // HR assigns Sam after the student submitted: the printed report now names Sam in both places.
    $placement->update(['supervisor_user_id' => $f['sam']->id]);
    $data = $service->printData($entry->fresh());
    expect($data['header']['supervisor_name'])->toBe('Sam Supervisor')->and($data['header']['supervisor_title'])->toBe('QA Lead')
        ->and($data['certifiedBy'])->toBe('Sam Supervisor')->and($data['certifiedOn'])->toBe('');

    // Certified by Sam: the form keeps him, even if the intern gets another supervisor later.
    $entry->update(['status' => \App\Models\JournalEntry::STATUS_ACKNOWLEDGED, 'reviewed_by_user_id' => $f['sam']->id, 'reviewed_at' => now(), 'header_snapshot' => [...$submittedBeforeAssignment, 'supervisor_name' => 'Sam Supervisor', 'supervisor_title' => 'QA Lead']]);
    $kim = svSupervisor($f['employer'], 'Kim Newsupervisor');
    $placement->update(['supervisor_user_id' => $kim->id]);
    expect($service->printData($entry->fresh())['header']['supervisor_name'])->toBe('Sam Supervisor')
        ->and($service->printData($entry->fresh())['certifiedBy'])->toBe('Sam Supervisor');
});
it('lets only an assigned supervisor decide the printed name; the student cannot change it', function () {
    $f = swSetup();
    $student = $f['mine']->student->user;
    $f['mine']->update(['supervisor_name' => 'Sam Supervisor', 'department' => 'IT Department']);
    \App\Models\AcademicSetting::current()->update(['ojt_departments' => ['IT Department', 'HR Department']]);

    $this->actingAs($student)->get(route('student.ojt.index', ['placement' => $f['mine']->id, 'tab' => 'progress']))->assertOk()
        ->assertSee('Set by your company')->assertSee('value="Sam Supervisor" readonly', false);

    $this->patch(route('student.ojt.details.update', $f['mine']), ['department' => 'HR Department', 'supervisor_name' => 'Fake Name'])->assertSessionHasNoErrors();
    expect($f['mine']->fresh()->supervisor_name)->toBe('Sam Supervisor')->and($f['mine']->fresh()->department)->toBe('HR Department');

    // With no supervisor assigned the student still sets the name, as before.
    $freeStudent = $f['free']->student->user;
    $this->actingAs($freeStudent)->patch(route('student.ojt.details.update', $f['free']), ['department' => 'IT Department', 'supervisor_name' => 'Maria Santos'])->assertSessionHasNoErrors();
    expect($f['free']->fresh()->supervisor_name)->toBe('Maria Santos');
});

it('keeps supervisors out of the admin role form, and shows their company in the users list', function () {
    $f = swSetup();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = User::factory()->create(['role' => UserRole::Student]);
    $update = fn (User $user, array $changes) => $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'is_active' => 1, ...$changes,
    ]);

    $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'supervisor']))->assertOk()
        ->assertSee('Sam Supervisor')->assertSee('Workflow Co')->assertSee('QA Lead');

    // Cannot turn a supervisor into another role, or another user into a supervisor; deactivating is fine.
    $update($f['sam'], ['role' => 'employer'])->assertSessionHasErrors('role');
    $update($student, ['role' => 'supervisor'])->assertSessionHasErrors('role');
    expect($f['sam']->fresh()->role)->toBe(UserRole::Supervisor)->and($student->fresh()->role)->toBe(UserRole::Student);
    $update($f['sam'], ['is_active' => 0])->assertSessionHasNoErrors();
    expect($f['sam']->fresh()->is_active)->toBeFalse()->and($f['mine']->fresh()->activeSupervisor())->toBeNull();
});
