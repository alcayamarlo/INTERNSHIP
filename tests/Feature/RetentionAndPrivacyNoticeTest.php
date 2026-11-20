<?php

use App\Enums\UserRole;
use App\Models\AccountDeletionRequest;
use App\Models\AppNotification;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountAnonymizer;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\RetentionReview;
use Illuminate\Console\Scheduling\Schedule;

/** A student account whose last sign-in was $monthsAgo months ago (null: never signed in, created that long ago). */
function rrStudent(string $name, ?int $monthsAgo, bool $neverSignedIn = false): Student
{
    $when = now()->subMonths($monthsAgo ?? 0);
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => $name, 'last_login_at' => $neverSignedIn ? null : $when, 'created_at' => $neverSignedIn ? $when : now()->subYears(10)]);

    return Student::create(['user_id' => $user->id, 'student_id_number' => 'SCC-20-'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT)]);
}

it('lists only accounts unused for the whole retention period, with an OJT that has ended counted as activity', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-19 09:00:00'));
    $old = rrStudent('Unused Four Years', 48);
    $recent = rrStudent('Signed In Last Year', 12);
    $justUnder = rrStudent('Almost Three Years', 35);
    $neverSignedIn = rrStudent('Never Signed In', 40, neverSignedIn: true);

    // Old sign-in, but the OJT ended a year ago: still in use.
    $ojtRecent = rrStudent('Old Login Recent OJT', 60);
    $company = svCompany();
    $placement = svPlacement($company['employer']);
    $placement->student->user->forceFill(['last_login_at' => now()->subYears(5), 'created_at' => now()->subYears(6)])->save();
    $placement->forceFill(['status' => Placement::STATUS_COMPLETED, 'end_date' => now()->subMonths(10)->toDateString()])->save();
    // Old sign-in and an OJT that ended 4 years ago: past it, and the OJT date is the basis.
    $oldOjt = svPlacement($company['employer']);
    $oldOjt->student->user->forceFill(['last_login_at' => now()->subYears(6), 'created_at' => now()->subYears(7)])->save();
    $oldOjt->forceFill(['status' => Placement::STATUS_COMPLETED, 'end_date' => now()->subYears(4)->toDateString()])->save();
    // Old sign-in but an OJT still running: never listed.
    $running = svPlacement($company['employer']);
    $running->student->user->forceFill(['last_login_at' => now()->subYears(6), 'created_at' => now()->subYears(7)])->save();
    // A request already waiting, an anonymised account, and a non-student.
    $waiting = rrStudent('Request Waiting', 50);
    AccountDeletionRequest::create(['user_id' => $waiting->user_id]);
    $gone = rrStudent('Already Anonymised', 50);
    $gone->user->forceFill(['anonymised_at' => now()])->save();
    User::factory()->create(['role' => UserRole::Coordinator, 'last_login_at' => now()->subYears(9)]);

    $names = app(RetentionReview::class)->candidates()->map(fn ($row) => $row->student->user->name)->all();

    expect($names)->toContain('Unused Four Years', 'Never Signed In', $oldOjt->student->user->name)
        ->not->toContain('Signed In Last Year', 'Almost Three Years', 'Old Login Recent OJT', 'Request Waiting', 'Already Anonymised', $running->student->user->name, $placement->student->user->name);

    $bases = app(RetentionReview::class)->candidates()->mapWithKeys(fn ($row) => [$row->student->user->name => $row->basis]);
    expect($bases['Unused Four Years'])->toBe('Last sign-in')->and($bases['Never Signed In'])->toBe('Never signed in, created')->and($bases[$oldOjt->student->user->name])->toBe('OJT ended');
});

it('uses the configured period', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-19 09:00:00'));
    rrStudent('Two Years Ago', 25);
    expect(app(RetentionReview::class)->candidates())->toHaveCount(0); // default: 3 years

    config(['privacy.retention_years' => 2]);
    expect(app(RetentionReview::class)->candidates())->toHaveCount(1);
});

it('reports with retention:review, notifies the admins only when asked and only when there is something, and deletes nothing', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-19 09:00:00'));
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inactiveAdmin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => false]);

    $this->artisan('retention:review', ['--notify' => true])->expectsOutputToContain('0 student accounts past it')->assertSuccessful();
    expect(AppNotification::where('type', 'retention_review')->count())->toBe(0);

    $old = rrStudent('Unused Four Years', 48);
    $this->artisan('retention:review')->expectsOutputToContain('1 student account past it')->expectsOutputToContain('Unused Four Years')->assertSuccessful();
    expect(AppNotification::where('type', 'retention_review')->count())->toBe(0); // listing alone does not notify

    $this->artisan('retention:review', ['--notify' => true])->assertSuccessful();
    expect(AppNotification::where('user_id', $admin->id)->where('type', 'retention_review')->count())->toBe(1)
        ->and(AppNotification::where('user_id', $inactiveAdmin->id)->count())->toBe(0);

    // Nothing was deleted, anonymised or requested.
    expect(User::find($old->user_id)->name)->toBe('Unused Four Years')->and(User::find($old->user_id)->anonymised_at)->toBeNull()->and(AccountDeletionRequest::count())->toBe(0);
    $this->assertDatabaseHas('system_logs', ['action' => 'retention_review']);

    // The notification opens the review page.
    $notice = AppNotification::where('user_id', $admin->id)->where('type', 'retention_review')->first();
    $this->actingAs($admin)->get(route('notifications.show', $notice))->assertRedirect(route('admin.retention'));
});

it('is scheduled monthly', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'retention:review'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 6 1 * *')     // the 1st of every month, 06:00
        ->and($event->timezone)->toBe('Asia/Manila')
        ->and($event->command)->toContain('--notify');
});

it('shows the list and opens a request that waits in the approval queue', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-19 09:00:00'));
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $old = rrStudent('Unused Four Years', 48);
    $recent = rrStudent('Signed In Last Year', 12);

    $this->actingAs($admin)->get(route('admin.retention'))->assertOk()->assertSee('Unused Four Years')->assertDontSee('Signed In Last Year')->assertSee('3-year retention period')->assertSee('data-retention-list', false);

    // A recent account is refused: it is not on the list.
    $this->post(route('admin.retention.request', $recent))->assertSessionHas('error');
    expect(AccountDeletionRequest::count())->toBe(0);

    $this->post(route('admin.retention.request', $old))->assertRedirect(route('admin.deletion-requests.index'));
    $request = AccountDeletionRequest::sole();
    expect($request->source)->toBe('retention')->and($request->status)->toBe('pending')->and($request->requested_by_user_id)->toBe($admin->id)->and($request->reason)->toContain('3 years');
    expect(User::find($old->user_id)->anonymised_at)->toBeNull(); // opening a request deletes nothing

    // Once a request waits, the account leaves the list, and a second request is refused.
    $this->get(route('admin.retention'))->assertDontSee('Unused Four Years');
    $this->post(route('admin.retention.request', $old))->assertSessionHas('error');
    $this->get(route('admin.deletion-requests.index'))->assertOk()->assertSee('Unused Four Years')->assertSee('retention review');
});

/* ---------- Privacy notice ---------- */

it('publishes the privacy notice to everyone, with the configured retention period and contact', function () {
    config(['privacy.retention_years' => 5, 'privacy.contact_email' => 'dpo@school.test', 'privacy.contact_name' => 'Ms. Reyes']);

    $this->get(route('pages.privacy'))->assertOk()
        ->assertSee('Privacy notice')->assertSee('RA 10173')->assertSee('5 years')
        ->assertSee('dpo@school.test')->assertSee('Ms. Reyes')->assertSee('National Privacy Commission')
        ->assertSee('Privacy and my data')->assertSee('without your name');

    config(['privacy.contact_email' => null]);
    $this->get(route('pages.privacy'))->assertOk()->assertSee('ask your coordinator');
});

it('links the privacy notice from the footer, the sign-in form and the student consent', function () {
    $url = route('pages.privacy');

    $this->get(route('home'))->assertOk()->assertSee('href="'.$url.'"', false);
    $this->get(route('login'))->assertOk()->assertSee('href="'.$url.'"', false);

    $student = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now(), 'must_change_password' => false]);
    Student::create(['user_id' => $student->id]); // no consent yet: the consent card is shown
    $this->actingAs($student)->get(route('student.profile.edit'))->assertOk()->assertSee('privacy notice')->assertSee('href="'.$url.'"', false);
    $this->get(route('student.privacy.index'))->assertOk()->assertSee('href="'.$url.'"', false);
});

it('puts the new pages in the right menus, and routes the notifications to them', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now(), 'must_change_password' => false]);
    Student::create(['user_id' => $student->id]);

    $this->actingAs($admin)->get(route('admin.logs'))->assertSee('Deletion Requests')->assertSee('Retention Review')->assertSee(route('admin.retention'));
    $this->actingAs($student)->get(route('student.dashboard'))->assertSee('Privacy and my data')->assertSee(route('student.privacy.index'));
    $this->actingAs($admin)->get(route('admin.logs'))->assertDontSee('Privacy and my data');

    $notifications = app(NotificationService::class);
    $forAdmin = $notifications->send($admin, 'deletion_request', 'Account deletion request', 'x', [], false, false);
    $forStudent = $notifications->send($student, 'deletion_decision', 'Not approved', 'x', [], false, false);
    $this->actingAs($admin)->get(route('notifications.show', $forAdmin))->assertRedirect(route('admin.deletion-requests.index'));
    $this->actingAs($student)->get(route('notifications.show', $forStudent))->assertRedirect(route('student.privacy.index'));
    // A student cannot be sent to the admin page: it shows the notification itself.
    $wrong = $notifications->send($student, 'deletion_request', 'x', 'x', [], false, false);
    $this->get(route('notifications.show', $wrong))->assertOk();
});

it('scrubs an email that is only mentioned inside longer log text', function () {
    $user = User::factory()->create(['role' => UserRole::Student, 'email' => 'someone.long@school.test']);
    Student::create(['user_id' => $user->id]);
    app(ActivityLogService::class)->log(null, 'note', ['text' => 'tried to sign in as someone.long@school.test from the lab', 'ok' => 'unrelated']);

    $result = app(AccountAnonymizer::class)->anonymise($user, null);

    $details = \App\Models\SystemLog::where('action', 'note')->first()->details;
    expect($result['log_entries_scrubbed'])->toBe(1)->and($details['text'])->toBe('[removed]')->and($details['ok'])->toBe('unrelated');
});
