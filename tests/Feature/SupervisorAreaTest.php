<?php

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\MessagingEligibility;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function saLog($placement, string $status = AttendanceLog::STATUS_PENDING): AttendanceLog
{
    return AttendanceLog::create([
        'placement_id' => $placement->id, 'work_date' => now()->subDay()->toDateString(), 'time_in' => '08:00', 'time_out' => '17:00',
        'break_minutes' => 60, 'hours' => 8, 'task_summary' => 'Worked on tasks', 'status' => $status,
    ]);
}

/** Company A with a supervisor (Sam) who has one intern, plus an intern nobody supervises, and Company B. */
function saSetup(): array
{
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $sam = svSupervisor($a['employer'], 'Sam Supervisor');
    $sam->forceFill(['password' => 'Secret#2026'])->save();

    return [
        'a' => $a, 'b' => $b, 'sam' => $sam,
        'mine' => svPlacement($a['employer'], $sam),
        'unassigned' => svPlacement($a['employer']),
        'other' => svPlacement($b['employer']),
    ];
}

it('signs a supervisor in to their own dashboard', function () {
    $f = saSetup();

    $this->post(route('login'), ['login' => $f['sam']->email, 'password' => 'Secret#2026'])->assertRedirect(route('supervisor.dashboard'));
    $this->get(route('supervisor.dashboard'))->assertOk()
        ->assertSee('Senior Engineer · Company A')->assertSee('Your interns')->assertSee('Supervisor workspace')
        ->assertSee($f['mine']->student->user->name)->assertDontSee($f['unassigned']->student->user->name);
    $this->get(route('supervisor.interns.index'))->assertOk()->assertSee('My Interns');
});

it('lists only the interns assigned to the supervisor', function () {
    $f = saSetup();
    $kim = svSupervisor($f['a']['employer'], 'Kim');
    $kimsIntern = svPlacement($f['a']['employer'], $kim);

    $page = $this->actingAs($f['sam'])->get(route('supervisor.interns.index'))->assertOk();
    $page->assertSee($f['mine']->student->user->name)
        ->assertDontSee($f['unassigned']->student->user->name)->assertDontSee($kimsIntern->student->user->name)->assertDontSee($f['other']->student->user->name)
        ->assertDontSee('Assign ticked interns') // assigning is for HR
        ->assertDontSee('No supervisor assigned');
});

it('lets the supervisor open and act on their own interns only', function () {
    $f = saSetup();
    $log = saLog($f['mine']);
    $unassignedLog = saLog($f['unassigned']);
    $this->actingAs($f['sam']);

    $this->get(route('supervisor.interns.show', $f['mine']))->assertOk()->assertSee('Time Record')->assertSee('Weekly Report');
    $this->patch(route('supervisor.interns.attendance.review', [$f['mine'], $log]), ['status' => 'approved'])->assertSessionHas('success');
    expect($log->fresh()->status)->toBe('approved');

    // Not their intern, another company's intern: nothing opens, nothing changes.
    $this->get(route('supervisor.interns.show', $f['unassigned']))->assertForbidden();
    $this->get(route('supervisor.interns.show', $f['other']))->assertForbidden();
    $this->patch(route('supervisor.interns.attendance.review', [$f['unassigned'], $unassignedLog]), ['status' => 'approved'])->assertForbidden();
    expect($unassignedLog->fresh()->status)->toBe('pending');
});

it('keeps the HR account read-only for an intern that has a supervisor, and in charge of the others', function () {
    $f = saSetup();
    $mineLog = saLog($f['mine']);
    $freeLog = saLog($f['unassigned']);
    $this->actingAs($f['a']['hrUser']);

    // Assigned: HR can look, with a note about who supervises, but cannot approve.
    $this->get(route('employer.interns.show', $f['mine']))->assertOk()
        ->assertSee('Sam Supervisor')->assertSee('supervises this intern')->assertDontSee('OJT schedule');
    $this->patch(route('employer.interns.attendance.review', [$f['mine'], $mineLog]), ['status' => 'approved'])->assertForbidden();
    expect($mineLog->fresh()->status)->toBe('pending');

    // Nobody assigned: HR covers it, exactly as before.
    $this->get(route('employer.interns.show', $f['unassigned']))->assertOk()->assertSee('OJT schedule')->assertDontSee('supervises this intern');
    $this->patch(route('employer.interns.attendance.review', [$f['unassigned'], $freeLog]), ['status' => 'approved'])->assertSessionHas('success');
});

it('keeps each area to its own role', function () {
    $f = saSetup();

    $this->actingAs($f['sam'])->get(route('employer.supervisors.index'))->assertForbidden();
    $this->get(route('employer.dashboard'))->assertForbidden();
    $this->get(route('coordinator.dashboard'))->assertForbidden();
    foreach ([$f['a']['hrUser'], $f['mine']->student->user] as $other) {
        $this->actingAs($other)->get(route('supervisor.dashboard'))->assertForbidden();
        $this->get(route('supervisor.interns.index'))->assertForbidden();
    }
});

it('lets a supervisor and their interns message each other, and nobody else\'s', function () {
    $f = saSetup();
    $messaging = app(MessagingEligibility::class);
    $myStudent = $f['mine']->student->user;
    $otherStudent = $f['unassigned']->student->user;

    expect($messaging->canSend($f['sam'], $myStudent)->allowed())->toBeTrue()
        ->and($messaging->canSend($myStudent, $f['sam'])->allowed())->toBeTrue()
        ->and($messaging->canSend($f['sam'], $otherStudent)->allowed())->toBeFalse()
        ->and($messaging->canSend($otherStudent, $f['sam'])->allowed())->toBeFalse()
        ->and($messaging->canSend($otherStudent, $f['sam'])->message())->toBe(MessagingEligibility::SUPERVISOR_NOT_ASSIGNED)
        ->and($messaging->canSend($f['sam'], $f['a']['hrUser'])->allowed())->toBeTrue(); // their own HR

    expect($messaging->startableContactIds($f['sam'])->all())->toContain($myStudent->id, $f['a']['hrUser']->id)->not->toContain($otherStudent->id)
        ->and($messaging->startableContactIds($myStudent)->all())->toContain($f['sam']->id)
        ->and($messaging->startableContactIds($otherStudent)->all())->not->toContain($f['sam']->id);

    // Through the real pages.
    $this->actingAs($myStudent)->post(route('messages.store', $f['sam']), ['body' => 'Good morning, sir.', 'submission_id' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasNoErrors();
    $this->actingAs($f['sam'])->get(route('messages.show', $myStudent))->assertOk()->assertSee('Good morning, sir.');
});

it('opens a supervisor\'s notification on the right tab of their own intern page', function () {
    $f = saSetup();
    $send = fn (array $data) => app(NotificationService::class)->send($f['sam'], 'ojt_update', 'Title', 'Message', $data, email: false);
    $id = $f['mine']->id;
    $this->actingAs($f['sam']);

    $this->get(route('notifications.show', $send(['placement_id' => $id, 'section' => 'weekly-journal'])))
        ->assertRedirect(route('supervisor.interns.show', ['placement' => $id, 'tab' => 'weekly-journal']));
    // Another company's intern never opens from a notification.
    $this->get(route('notifications.show', $send(['placement_id' => $f['other']->id, 'section' => 'evaluation'])))->assertOk();

    // A notice about several interns (no single record) opens the supervisor's intern list.
    $this->get(route('notifications.show', $send([])))->assertRedirect(route('supervisor.interns.index'));
});

it('links the "interns assigned" notice to the intern, or to the list when several were assigned', function () {
    $f = saSetup();
    $employer = $f['mine']->employer;
    $service = app(\App\Services\SupervisorService::class);
    $hr = $employer->user;
    [$first, $second, $third] = [svPlacement($employer), svPlacement($employer), svPlacement($employer)]; // not assigned yet

    $service->assignMany($employer, [$first->id], $f['sam']->id, $hr);
    $one = \App\Models\AppNotification::where('user_id', $f['sam']->id)->where('title', 'New interns assigned')->latest('id')->firstOrFail();
    $this->actingAs($f['sam'])->get(route('notifications.show', $one))
        ->assertRedirect(route('supervisor.interns.show', ['placement' => $first->id, 'tab' => 'progress']));

    $service->assignMany($employer, [$second->id, $third->id], $f['sam']->id, $hr);
    $many = \App\Models\AppNotification::where('user_id', $f['sam']->id)->where('title', 'New interns assigned')->latest('id')->firstOrFail();
    $this->get(route('notifications.show', $many))->assertRedirect(route('supervisor.interns.index'));
});

it('shows the supervisor the emergency contact of an intern the company placed, like the HR account', function () {
    $f = saSetup();
    $f['mine']->student->update(['emergency_contact_name' => 'Maria Emergency', 'emergency_contact_phone' => '09175550000']);

    $this->actingAs($f['sam'])->get(route('supervisor.interns.show', $f['mine']))->assertOk()->assertSee('Maria Emergency')->assertSee('09175550000');
});

it('lists the OJT sections of an intern in the sidebar instead of a tab strip on the page', function () {
    $f = saSetup();
    $id = $f['mine']->id;

    $html = $this->actingAs($f['sam'])->get(route('supervisor.interns.show', ['placement' => $id, 'tab' => 'weekly-journal']))->assertOk()->getContent();

    // Five links under "My Interns", each opening its own section; the open one is selected.
    foreach (['progress' => 'Progress', 'time-record' => 'Time Record', 'weekly-journal' => 'Weekly Report', 'monthly-evaluation' => 'Monthly Evaluation', 'evaluation' => 'Final Evaluation'] as $key => $label) {
        expect($html)->toContain('id="ojt-tab-'.$key.'" class="sb-subnav-link"')
            ->and($html)->toContain('tab='.$key)->and($html)->toContain($label);
    }
    expect($html)->toMatch('/id="ojt-tab-weekly-journal"[^>]*aria-selected="true"/')
        ->and($html)->toMatch('/id="ojt-tab-time-record"[^>]*aria-selected="false"/')
        // The sidebar list sits right after the active "My Interns" link, and the page no longer has its own tab bar.
        ->and(strpos($html, 'class="sb-subnav"'))->toBeGreaterThan(strpos($html, 'My Interns'))
        ->and($html)->not->toContain('class="sb-tabbar"')
        ->and($html)->toContain('id="ojt-panel-evaluation"');

    // The HR account gets the same list under "Interns" for an intern it covers.
    $company = $f['mine']->employer;
    $this->actingAs($company->user)->get(route('employer.interns.show', ['placement' => $id]))->assertOk()->assertSee('class="sb-subnav-link"', false);
});
