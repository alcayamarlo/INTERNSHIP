<?php

use App\Enums\UserRole;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

it('prevents duplicate messages when the same submission is retried', function () {
    $sender = User::factory()->create(['role' => UserRole::Student]);
    $recipient = User::factory()->create(['role' => UserRole::Coordinator]);
    $submissionId = (string) Str::uuid();
    $payload = [
        'body' => 'Please review my latest update.',
        'submission_id' => $submissionId,
    ];

    Cache::flush();

    $this->actingAs($sender)
        ->from(route('messages.show', $recipient))
        ->post(route('messages.store', $recipient), $payload)
        ->assertRedirect();

    $this->post(route('messages.store', $recipient), $payload)
        ->assertRedirect()
        ->assertSessionHas('success', 'Message already sent.');

    expect(Message::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $recipient->id)
        ->where('body', $payload['body'])
        ->count())->toBe(1);
});

it('renders a submission key and disables the send button while submitting', function () {
    $sender = User::factory()->create(['role' => UserRole::Student]);
    $recipient = User::factory()->create(['role' => UserRole::Coordinator]);

    $this->actingAs($sender)
        ->get(route('messages.show', $recipient))
        ->assertOk()
        ->assertSee('name="submission_id"', false)
        ->assertSee('data-no-loading="true"', false)
        ->assertSee('messageForm.dataset.submitting', false)
        ->assertSee('sendButton.disabled = true', false);
});

it('renders Enter-to-send behavior for every messaging role', function () {
    $users = [];
    foreach (UserRole::all() as $role) {
        $users[$role->value] = User::factory()->create(['role' => $role]);
    }

    foreach ($users as $sender) {
        $recipient = $sender->isRole(UserRole::Student)
            ? $users[UserRole::Coordinator->value]
            : $users[UserRole::Student->value];

        $this->actingAs($sender)
            ->get(route('messages.show', $recipient))
            ->assertOk()
            ->assertSee("event.key !== 'Enter' || event.shiftKey || event.isComposing", false)
            ->assertSee('messageForm.requestSubmit()', false)
            ->assertSee('data-no-loading="true"', false)
            ->assertSee('spinner-border spinner-border-sm', false);
    }
});

it('sends a message asynchronously and rotates the retry token', function () {
    $sender = User::factory()->create(['role' => UserRole::Student]);
    $recipient = User::factory()->create(['role' => UserRole::Coordinator]);
    $submissionId = (string) Str::uuid();

    $this->actingAs($sender)
        ->postJson(route('messages.store', $recipient), [
            'body' => 'An asynchronous message.',
            'submission_id' => $submissionId,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'An asynchronous message.')
        ->assertJsonStructure(['created_at', 'submission_id']);

    expect(Message::where('sender_id', $sender->id)->count())->toBe(1);
});

it('allows students to message employers but excludes students from the new-contact picker', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);
    $employer = User::factory()->create(['role' => UserRole::Employer]);

    $this->actingAs($student)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee($employer->name)
        ->assertDontSee($otherStudent->name);

    $this->postJson(route('messages.store', $employer), [
        'body' => 'Hello, employer.',
        'submission_id' => (string) Str::uuid(),
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect(Message::where('sender_id', $student->id)->where('receiver_id', $employer->id)->count())->toBe(1);
});

it('blocks student-to-student start and send routes with a clear error', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)
        ->get(route('messages.show', $otherStudent))
        ->assertForbidden();

    $this->postJson(route('messages.store', $otherStudent), [
        'body' => 'This must be blocked.',
        'submission_id' => (string) Str::uuid(),
    ])->assertForbidden()
        ->assertJsonPath('message', 'Students cannot message other students.');

    expect(Message::where('sender_id', $student->id)->where('receiver_id', $otherStudent->id)->exists())->toBeFalse();
});

it('allows employers to message students', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    $student = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($employer)
        ->postJson(route('messages.store', $student), [
            'body' => 'Hello, student.',
            'submission_id' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('success', true);

    expect(Message::where('sender_id', $employer->id)->where('receiver_id', $student->id)->count())->toBe(1);
});

it('rejects a tampered student recipient id on the server', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $tamperedRecipient = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)
        ->postJson(route('messages.store', ['user' => $tamperedRecipient->id]), [
            'body' => 'Attempt with altered recipient.',
            'submission_id' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('message', 'Students cannot message other students.');

    expect(Message::where('sender_id', $student->id)->where('receiver_id', $tamperedRecipient->id)->exists())->toBeFalse();
});

it('allows non-student roles to message every other role', function () {
    $users = [];
    foreach (UserRole::all() as $role) {
        $users[$role->value] = User::factory()->create([
            'name' => 'Message recipient '.$role->value,
            'role' => $role,
        ]);
    }

    foreach ($users as $sender) {
        if ($sender->isRole(UserRole::Student)) {
            continue;
        }

        $this->actingAs($sender)
            ->get(route('messages.index'))
            ->assertOk();

        foreach ($users as $recipient) {
            if ($sender->is($recipient)) {
                continue;
            }

            $this->get(route('messages.index'))
                ->assertSee($recipient->name);

            $this->actingAs($sender)
                ->from(route('messages.show', $recipient))
                ->post(route('messages.store', $recipient), [
                    'body' => 'Cross-role message.',
                    'submission_id' => (string) Str::uuid(),
                ])
                ->assertRedirect(route('messages.show', $recipient))
                ->assertSessionHasNoErrors();
        }
    }

    expect(Message::count())->toBe(9);
});
