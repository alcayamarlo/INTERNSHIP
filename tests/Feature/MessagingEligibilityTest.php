<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Events\MessageSent;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Message;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\MessagingEligibility;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** A 4th-year student with an assigned coordinator, plus a company with one open internship. */
function chatSetup(): array
{
    $institution = Institution::create(['name' => 'St. Cecilia\'s College-Cebu']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
    ]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme Corp', 'contact_person' => 'HR']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);

    return compact('studentUser', 'student', 'coordinatorUser', 'employerUser', 'employer', 'internship', 'institution');
}

function chatApply(array $setup, ApplicationStatus $status): InternshipApplication
{
    return InternshipApplication::create([
        'internship_id' => $setup['internship']->id,
        'student_id' => $setup['student']->id,
        'status' => $status,
        'match_percentage' => 0,
        'applied_at' => now(),
    ]);
}

function chatSend(User $from, User $to)
{
    return test()->actingAs($from)->postJson(route('messages.store', $to), [
        'body' => 'Hello '.Str::random(6),
        'submission_id' => (string) Str::uuid(),
    ]);
}

it('lets a student always message their assigned coordinator, but not another coordinator', function () {
    $setup = chatSetup();
    chatSend($setup['studentUser'], $setup['coordinatorUser'])->assertOk();
    chatSend($setup['coordinatorUser'], $setup['studentUser'])->assertOk();

    $otherCoordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $otherCoordinator->id, 'institution_id' => Institution::create(['name' => 'Other School'])->id]);
    chatSend($setup['studentUser'], $otherCoordinator)->assertForbidden()
        ->assertJsonPath('message', MessagingEligibility::COORDINATOR_NOT_ASSIGNED);
    chatSend($otherCoordinator, $setup['studentUser'])->assertForbidden()
        ->assertJsonPath('message', MessagingEligibility::STUDENT_NOT_ASSIGNED);
});

it('blocks messaging a company before applying or while the application is pending: 403, nothing saved or broadcast', function () {
    Event::fake([MessageSent::class]);
    $setup = chatSetup();

    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden()
        ->assertJsonPath('message', 'You can message this company once your application is marked Reviewed.');
    chatSend($setup['employerUser'], $setup['studentUser'])->assertForbidden();

    chatApply($setup, ApplicationStatus::Applied);
    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden()
        ->assertJsonPath('message', 'You can message this company once your application is marked Reviewed.');
    $this->get(route('messages.show', $setup['employerUser']))->assertForbidden();

    expect(Message::count())->toBe(0);
    Event::assertNotDispatched(MessageSent::class);
});

it('opens the company chat at Reviewed, Interview, and Accepted', function (ApplicationStatus $status) {
    $setup = chatSetup();
    chatApply($setup, $status);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();
    chatSend($setup['employerUser'], $setup['studentUser'])->assertOk();
    expect(Message::count())->toBe(2);
})->with([
    'reviewed' => [ApplicationStatus::Reviewed],
    'interview' => [ApplicationStatus::Interview],
    'accepted' => [ApplicationStatus::Accepted],
]);

it('keeps the chat closed for a pending invitation and opens it right after accepting', function () {
    $setup = chatSetup();
    Resume::create(['student_id' => $setup['student']->id, 'file_path' => 'resumes/r.pdf', 'content' => [], 'generated_at' => now()]);
    $invitation = app(InvitationService::class)->invite($setup['employerUser'], $setup['student'], $setup['internship'], null);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden();
    chatSend($setup['employerUser'], $setup['studentUser'])->assertForbidden();

    app(InvitationService::class)->accept($invitation, $setup['studentUser']);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();
    // One combined notification, carrying the live-unlock hint.
    $notifications = AppNotification::where('user_id', $setup['studentUser']->id)->where('type', 'application_update')->get();
    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->message)->toContain('You can now message the company.')
        ->and($notifications->first()->data['messaging_open_with'])->toBe($setup['employerUser']->id);
});

it('locks the chat when Declined (default) and keeps old messages read-only', function () {
    $setup = chatSetup();
    $application = chatApply($setup, ApplicationStatus::Reviewed);
    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();

    $this->actingAs($setup['employerUser'])
        ->putJson(route('employer.applicants.status', $application), ['status' => 'rejected'])
        ->assertOk();

    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden()
        ->assertJsonPath('message', MessagingEligibility::COMPANY_DECLINED_FOR_STUDENT);
    $this->get(route('messages.show', $setup['employerUser']))
        ->assertOk()
        ->assertSee(Message::first()->body)
        ->assertSee(MessagingEligibility::COMPANY_DECLINED_FOR_STUDENT);
    $this->get(route('messages.index'))->assertSee('bi-lock-fill', false);

    $notice = AppNotification::where('user_id', $setup['studentUser']->id)->latest('id')->first();
    expect($notice->message)->toContain('Messaging with Acme Corp is now closed')
        ->and(AppNotification::where('user_id', $setup['studentUser']->id)->count())->toBe(1);
});

it('keeps the chat open after Declined when the config says so', function () {
    config(['messaging.declined_keeps_chat_open' => true]);
    $setup = chatSetup();
    chatApply($setup, ApplicationStatus::Rejected);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();
});

it('locks the chat when the application is Withdrawn', function () {
    $setup = chatSetup();
    $application = chatApply($setup, ApplicationStatus::Interview);
    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();

    $application->update(['status' => ApplicationStatus::Withdrawn]);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden()
        ->assertJsonPath('message', MessagingEligibility::COMPANY_WITHDRAWN_FOR_STUDENT);
    chatSend($setup['employerUser'], $setup['studentUser'])->assertForbidden();
});

it('checks the status at send time, so moving back to pending locks sending again', function () {
    $setup = chatSetup();
    $application = chatApply($setup, ApplicationStatus::Reviewed);

    $this->actingAs($setup['studentUser'])->get(route('messages.show', $setup['employerUser']))->assertOk()->assertSee('name="body"', false);
    $application->update(['status' => ApplicationStatus::Applied]);

    chatSend($setup['studentUser'], $setup['employerUser'])->assertForbidden();
    expect(Message::count())->toBe(0);
});

it('tells the student when Reviewed opens the chat, with the live-unlock hint', function () {
    $setup = chatSetup();
    $application = chatApply($setup, ApplicationStatus::Applied);

    $this->actingAs($setup['employerUser'])
        ->putJson(route('employer.applicants.status', $application), ['status' => 'reviewed'])
        ->assertOk();

    $notice = AppNotification::where('user_id', $setup['studentUser']->id)->sole();
    expect($notice->message)->toBe('Your application for Web Intern was reviewed. You can now message the company.')
        ->and($notice->data['messaging_open_with'])->toBe($setup['employerUser']->id);
});

it('shows companies the student applied to as locked in the Messages list', function () {
    $setup = chatSetup();
    chatApply($setup, ApplicationStatus::Applied);

    $this->actingAs($setup['studentUser'])
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee('Companies you applied to')
        ->assertSee('Acme Corp')
        ->assertSee('You can message this company once your application is marked Reviewed.');
});

it('does not let a student read another student\'s conversation', function () {
    $setup = chatSetup();
    chatApply($setup, ApplicationStatus::Reviewed);
    chatSend($setup['studentUser'], $setup['employerUser'])->assertOk();
    $intruder = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $intruder->id]);

    // A thread is always between the signed-in user and the other person; the intruder sees none of it.
    $this->actingAs($intruder)->get(route('messages.show', $setup['studentUser']))->assertForbidden();
    $this->get(route('messages.show', $setup['employerUser']))->assertForbidden();
    $this->get(route('messages.index'))->assertDontSee(Message::first()->body);
});

it('rejects subscribing to another user\'s private channel', function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => '1',
        'broadcasting.connections.pusher.options.cluster' => 'ap1',
    ]);
    app(BroadcastManager::class)->purge();
    require base_path('routes/channels.php');
    $setup = chatSetup();

    $this->actingAs($setup['studentUser'])
        ->post('/broadcasting/auth', ['channel_name' => 'private-App.Models.User.'.$setup['employerUser']->id, 'socket_id' => '1234.5678'])
        ->assertForbidden();
    $this->post('/broadcasting/auth', ['channel_name' => 'private-App.Models.User.'.$setup['studentUser']->id, 'socket_id' => '1234.5678'])
        ->assertOk();
});
