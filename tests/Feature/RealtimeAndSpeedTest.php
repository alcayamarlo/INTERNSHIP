<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Events\LiveUpdate;
use App\Events\NotificationCreated;
use App\Jobs\SendNotification;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/** Turns on what production uses: a real broadcaster and a queue worker. */
function withQueueWorker(): void
{
    config(['queue.default' => 'database', 'broadcasting.default' => 'pusher', 'mail.default' => 'log']);
}

function liveFixture(): array
{
    $institution = Institution::create(['name' => 'Live U', 'address' => 'Cebu', 'contact_email' => 'live@example.edu']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Live Co', 'contact_person' => 'HR']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Live Intern', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id, 'student_id' => $student->id,
        'status' => ApplicationStatus::Interview, 'match_percentage' => 80,
    ]);
    $bystander = User::factory()->create(['role' => UserRole::Student]);

    return compact('studentUser', 'coordinatorUser', 'employerUser', 'application', 'bystander');
}

it('keeps the Pusher call for notifications out of the request when a queue worker runs', function () {
    expect(new NotificationCreated(new \App\Models\AppNotification))->toBeInstanceOf(ShouldBroadcast::class)
        ->not->toBeInstanceOf(ShouldBroadcastNow::class);

    withQueueWorker();
    Queue::fake();
    $user = User::factory()->create();

    app(NotificationService::class)->send($user, 'test', 'Hello', 'World');

    // The bell row exists right away; the push and the email wait for the worker.
    $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'Hello']);
    Queue::assertPushed(BroadcastEvent::class, fn ($job) => $job->event instanceof NotificationCreated);
    Queue::assertPushed(SendNotification::class);
});

it('queues announcement emails instead of sending one per recipient inside the request', function () {
    withQueueWorker();
    Queue::fake();
    Mail::fake();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(3)->create(['role' => UserRole::Student]);

    $this->actingAs($admin)->post(route('admin.announcements.store'), [
        'title' => 'Orientation', 'content' => 'Monday 8 AM.', 'target_role' => UserRole::Student->value,
    ])->assertRedirect();

    Queue::assertPushed(SendNotification::class, 3);
    Mail::assertNothingSent();
});

it('pings everyone involved once, with all changed topics, when an application is accepted', function () {
    withQueueWorker();
    Event::fake([LiveUpdate::class]);
    $f = liveFixture();

    $this->actingAs($f['employerUser'])
        ->put(route('employer.applicants.status', $f['application']), ['status' => 'accepted'])
        ->assertSessionHas('success');

    // One push for the whole click (application + placement saved), not one per saved record.
    Event::assertDispatchedTimes(LiveUpdate::class, 1);
    Event::assertDispatched(LiveUpdate::class, function (LiveUpdate $event) use ($f) {
        sort($event->topics);

        return $event->topics === ['applications', 'placements']
            && in_array($f['studentUser']->id, $event->userIds, true)
            && in_array($f['employerUser']->id, $event->userIds, true)
            && in_array($f['coordinatorUser']->id, $event->userIds, true)
            && ! in_array($f['bystander']->id, $event->userIds, true)
            && $event->broadcastWith() === ['topics' => $event->topics];
    });
});

it('sends no live pings without a queue worker, so clicks never wait on Pusher', function () {
    config(['queue.default' => 'sync', 'broadcasting.default' => 'pusher']);
    Event::fake([LiveUpdate::class]);
    $f = liveFixture();

    $this->actingAs($f['employerUser'])
        ->put(route('employer.applicants.status', $f['application']), ['status' => 'accepted']);

    Event::assertNotDispatched(LiveUpdate::class);
});

it('marks the lists people keep open as live regions', function () {
    $f = liveFixture();
    $this->actingAs($f['employerUser'])
        ->put(route('employer.applicants.status', $f['application']), ['status' => 'accepted']);

    $this->actingAs($f['coordinatorUser'])->get(route('coordinator.placements.index'))
        ->assertSee('data-live-region="placement-table"', false)
        ->assertSee('data-live-region="placement-summary"', false);
    $this->actingAs($f['employerUser'])->get(route('employer.applicants.index'))
        ->assertSee('data-live-region="applicants"', false);
    $this->actingAs($f['studentUser'])->get(route('student.applications.index'))
        ->assertSee('data-live-region="my-applications"', false);
    $this->actingAs($f['studentUser'])->get(route('notifications.index'))
        ->assertSee('data-live-region="notifications"', false);
});
