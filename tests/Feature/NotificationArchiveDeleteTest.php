<?php

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Student;
use App\Models\User;

function noteFor(User $user, array $attributes = []): AppNotification
{
    return AppNotification::create([
        'user_id' => $user->id,
        'type' => 'application_update',
        'title' => $attributes['title'] ?? 'Update '.uniqid(),
        'message' => 'Something changed.',
        ...$attributes,
    ]);
}

function noteStudent(): User
{
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    return $user;
}

it('archives into the Archived tab, out of the inbox, and unarchives back', function () {
    $user = noteStudent();
    $note = noteFor($user, ['title' => 'Interview scheduled']);

    $this->actingAs($user)->postJson(route('notifications.archive', $note))
        ->assertOk()
        ->assertJsonPath('counts.active', 0)
        ->assertJsonPath('counts.archived', 1)
        ->assertJsonPath('unread_count', 0);

    $this->get(route('notifications.index'))->assertDontSee('Interview scheduled');
    $this->get(route('notifications.index', ['view' => 'archived']))->assertSee('Interview scheduled')->assertSee('Unarchive');

    $this->postJson(route('notifications.restore', $note))
        ->assertOk()
        ->assertJsonPath('counts.active', 1)
        ->assertJsonPath('unread_count', 1);
    expect($note->fresh()->is_archived)->toBeFalse();
});

it('returns the real unread count after archiving, so the badge is not reset to zero', function () {
    $user = noteStudent();
    $archiveMe = noteFor($user);
    noteFor($user);
    noteFor($user);

    $this->actingAs($user)->postJson(route('notifications.archive', $archiveMe))
        ->assertJsonPath('unread_count', 2);
});

it('soft deletes to Trash, restores, and only permanently deletes from Trash', function () {
    $user = noteStudent();
    $note = noteFor($user, ['title' => 'Placement validated']);

    $this->actingAs($user)->deleteJson(route('notifications.destroy', $note))
        ->assertOk()
        ->assertJsonPath('counts.trash', 1)
        ->assertJsonPath('counts.active', 0);
    expect(AppNotification::find($note->id))->toBeNull()
        ->and(AppNotification::withTrashed()->find($note->id)->trashed())->toBeTrue();

    // Deleting again (double click / second tab) is still a success, not a 404.
    $this->deleteJson(route('notifications.destroy', $note))->assertOk();

    $this->get(route('notifications.index'))->assertDontSee('Placement validated');
    $this->get(route('notifications.index', ['view' => 'trash']))->assertSee('Placement validated')->assertSee('Delete forever');
    $this->get(route('notifications.show', $note))->assertNotFound();

    $this->postJson(route('notifications.restore-deleted', $note))->assertOk()->assertJsonPath('counts.active', 1);
    expect(AppNotification::find($note->id))->not->toBeNull();

    // Not in Trash: permanent delete is refused.
    $this->deleteJson(route('notifications.force-destroy', $note))->assertStatus(409);

    $this->deleteJson(route('notifications.destroy', $note));
    $this->deleteJson(route('notifications.force-destroy', $note))->assertOk()->assertJsonPath('counts.trash', 0);
    expect(AppNotification::withTrashed()->find($note->id))->toBeNull();
});

it('never lets a user archive, delete, restore, or purge someone else\'s notification', function () {
    $owner = noteStudent();
    $intruder = noteStudent();
    $note = noteFor($owner);
    $trashed = noteFor($owner);
    $trashed->delete();

    $this->actingAs($intruder);
    $this->postJson(route('notifications.archive', $note))->assertForbidden();
    $this->postJson(route('notifications.restore', $note))->assertForbidden();
    $this->postJson(route('notifications.read', $note))->assertForbidden();
    $this->deleteJson(route('notifications.destroy', $note))->assertForbidden();
    $this->postJson(route('notifications.restore-deleted', $trashed))->assertForbidden();
    $this->deleteJson(route('notifications.force-destroy', $trashed))->assertForbidden();

    // Bulk with the owner's ids changes nothing and reports zero.
    foreach (['read', 'archive', 'delete', 'restore', 'force-delete'] as $action) {
        $this->postJson(route('notifications.bulk'), ['action' => $action, 'ids' => [$note->id, $trashed->id]])
            ->assertOk()
            ->assertJsonPath('ids', []);
    }

    $note->refresh();
    expect($note->read_at)->toBeNull()
        ->and($note->archived_at)->toBeNull()
        ->and($note->trashed())->toBeFalse()
        ->and(AppNotification::withTrashed()->find($trashed->id))->not->toBeNull();
});

it('runs bulk read, archive, unarchive, delete, restore, and delete forever on the selection', function () {
    $user = noteStudent();
    [$a, $b, $c] = [noteFor($user), noteFor($user), noteFor($user)];
    $this->actingAs($user);

    $this->postJson(route('notifications.bulk'), ['action' => 'read', 'ids' => [$a->id, $b->id]])
        ->assertOk()->assertJsonPath('ids', [$a->id, $b->id])->assertJsonPath('unread_count', 1)
        ->assertJsonPath('message', '2 notifications marked as read.');

    $this->postJson(route('notifications.bulk'), ['action' => 'archive', 'ids' => [$a->id, $b->id, $c->id]])
        ->assertJsonPath('counts.archived', 3)->assertJsonPath('counts.active', 0)->assertJsonPath('unread_count', 0);

    $this->postJson(route('notifications.bulk'), ['action' => 'unarchive', 'ids' => [$c->id]])
        ->assertJsonPath('counts.active', 1)->assertJsonPath('unread_count', 1);

    $this->postJson(route('notifications.bulk'), ['action' => 'delete', 'ids' => [$a->id, $b->id, $c->id]])
        ->assertJsonPath('counts.trash', 3)->assertJsonPath('counts.archived', 0)->assertJsonPath('unread_count', 0);

    $this->postJson(route('notifications.bulk'), ['action' => 'restore', 'ids' => [$c->id]])
        ->assertJsonPath('ids', [$c->id])->assertJsonPath('counts.trash', 2);

    $this->postJson(route('notifications.bulk'), ['action' => 'force-delete', 'ids' => [$a->id, $b->id, $c->id]])
        // c is not in Trash any more, so only a and b are purged.
        ->assertJsonPath('ids', [$a->id, $b->id])->assertJsonPath('counts.trash', 0);

    expect(AppNotification::withTrashed()->where('user_id', $user->id)->pluck('id')->all())->toBe([$c->id]);
});

it('rejects an empty selection or an unknown bulk action with a message', function () {
    $user = noteStudent();
    $this->actingAs($user);

    $this->postJson(route('notifications.bulk'), ['action' => 'archive', 'ids' => []])
        ->assertStatus(422)->assertJsonValidationErrors(['ids' => 'Select at least one notification.']);
    $this->postJson(route('notifications.bulk'), ['action' => 'explode', 'ids' => [1]])
        ->assertStatus(422)->assertJsonValidationErrors('action');
});

it('keeps archived and deleted notifications out of the dashboard, bell, and unread count', function () {
    $user = noteStudent();
    noteFor($user, ['title' => 'Visible one']);
    noteFor($user, ['title' => 'Archived one', 'archived_at' => now()]);
    noteFor($user, ['title' => 'Deleted one'])->delete();

    $this->actingAs($user)->getJson(route('notifications.unread'))->assertJsonPath('count', 1);
    $this->get(route('student.dashboard'))
        ->assertSee('Visible one')
        ->assertDontSee('Archived one')
        ->assertDontSee('Deleted one');
});

it('works without JavaScript too (normal form posts redirect back)', function () {
    $user = noteStudent();
    $note = noteFor($user);

    $this->actingAs($user)->from(route('notifications.index'))
        ->delete(route('notifications.destroy', $note))
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success', 'Notification moved to Trash.');
});
