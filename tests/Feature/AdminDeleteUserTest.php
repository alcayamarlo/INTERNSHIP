<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a Delete button on active users but not on your own row', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $other = User::factory()->create(['role' => UserRole::Student, 'name' => 'Mistaken Account']);

    $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

    expect($html)->toContain(route('admin.users.delete-now', $other))
        ->and($html)->not->toContain(route('admin.users.delete-now', $admin));
});

it('deletes an account that has no OJT records, in one step', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $mistake = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($admin)->delete(route('admin.users.delete-now', $mistake))
        ->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $mistake->id]);
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'admin_permanently_delete_user']);
});

it('refuses to delete an account that has OJT records and tells the admin to archive it', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    ['employer' => $employer] = svCompany();
    $supervisor = svSupervisor($employer);
    $placement = svPlacement($employer, $supervisor);
    $studentUser = $placement->student->user;

    // The intern, the company's HR account and the supervisor all keep their account.
    foreach ([$studentUser, $employer->user, $supervisor] as $user) {
        $this->actingAs($admin)->delete(route('admin.users.delete-now', $user))
            ->assertRedirect()->assertSessionHas('error', fn ($message) => str_contains($message, 'Archive the account instead.'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
    $this->assertDatabaseHas('placements', ['id' => $placement->id]);
});

it('never deletes your own account or the last active administrator', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $other = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->delete(route('admin.users.delete-now', $admin))->assertForbidden();

    // Two active admins: one may delete the other. Then the last one is left alone.
    $this->actingAs($admin)->delete(route('admin.users.delete-now', $other))->assertSessionHas('success');
    $this->assertDatabaseMissing('users', ['id' => $other->id]);
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

it('lets only an administrator use the Delete button', function () {
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    $victim = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($coordinator)->delete(route('admin.users.delete-now', $victim))->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $victim->id]);
});
