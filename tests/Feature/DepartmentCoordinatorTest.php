<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** An admin, a school and two departments (programs). */
function departmentSetup(): array
{
    return [
        'admin' => User::factory()->create(['role' => UserRole::Admin]),
        'institution' => Institution::create(['name' => 'SCC', 'verified_at' => now()]),
        'bsit' => Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'required_internship_hours' => 486]),
        'bsba' => Program::create(['name' => 'BS Business Administration', 'code' => 'BSBA', 'required_internship_hours' => 486]),
    ];
}

function departmentCoordinator(array $d, Program $program, string $name = 'Maria Santos'): User
{
    $user = User::factory()->create(['role' => UserRole::Coordinator, 'name' => $name]);
    Coordinator::create(['user_id' => $user->id, 'institution_id' => $d['institution']->id, 'program_id' => $program->id]);

    return $user;
}

function departmentStudent(array $d, ?Program $program): Student
{
    return Student::create([
        'user_id' => User::factory()->create(['role' => UserRole::Student])->id,
        'institution_id' => $d['institution']->id,
        'program_id' => $program?->id,
    ]);
}

it('requires a program without a coordinator when an admin creates one, and allows only one per program', function () {
    $d = departmentSetup();
    $payload = fn (string $email, ?int $programId) => [
        'name' => 'New Coordinator', 'email' => $email, 'password' => 'Password#2026x', 'password_confirmation' => 'Password#2026x',
        'role' => UserRole::Coordinator->value, 'institution_id' => $d['institution']->id, 'program_id' => $programId,
    ];
    $this->actingAs($d['admin']);

    $this->post(route('admin.users.store'), $payload('none@example.test', null))
        ->assertSessionHasErrors(['program_id' => 'Choose the department this coordinator will handle.']);

    $this->post(route('admin.users.store'), $payload('it@example.test', $d['bsit']->id))->assertSessionHasNoErrors();
    expect(User::where('email', 'it@example.test')->firstOrFail()->coordinator->program_id)->toBe($d['bsit']->id);

    // BSIT now has its coordinator: the form lists only BSBA, and a second BSIT coordinator is refused.
    $this->get(route('admin.users.create'))->assertOk()
        ->assertSee('BS Business Administration (BSBA)')
        ->assertDontSee('BS Information Technology (BSIT)');
    $this->post(route('admin.users.store'), $payload('it2@example.test', $d['bsit']->id))
        ->assertSessionHasErrors(['program_id' => 'This program already has a coordinator.']);
    expect(User::where('email', 'it2@example.test')->exists())->toBeFalse()
        ->and(Coordinator::where('program_id', $d['bsit']->id)->count())->toBe(1);
});

it('gives a program one coordinator, and blocks deleting a program that has one', function () {
    $d = departmentSetup();
    departmentCoordinator($d, $d['bsit']);
    $student = departmentStudent($d, $d['bsit']);

    expect(Coordinator::departmentHasRoom($d['bsit']->id))->toBeFalse()->and(Coordinator::departmentHasRoom($d['bsba']->id))->toBeTrue()
        ->and($d['bsit']->coordinators()->count())->toBe(1)
        // The coordinator sees the department's students.
        ->and($d['bsit']->coordinators->every(fn (Coordinator $coordinator) => $coordinator->canAccessStudent($student)))->toBeTrue()
        ->and(fn () => $d['bsit']->delete())->toThrow(QueryException::class)
        ->and($d['bsba']->delete())->toBeTrue(); // no coordinator, so it can go
});

it('limits a coordinator to the students of their own department', function () {
    $d = departmentSetup();
    $coordinatorUser = departmentCoordinator($d, $d['bsit']);
    $mine = departmentStudent($d, $d['bsit']);
    $otherDepartment = departmentStudent($d, $d['bsba']);
    $noDepartment = departmentStudent($d, null);

    $this->actingAs($coordinatorUser)->getJson(route('coordinator.students.index'))
        ->assertOk()
        ->assertJsonCount(1, 'students')
        ->assertJsonFragment(['id' => $mine->id]);
    $this->get(route('coordinator.students.show', $mine))->assertOk();
    $this->get(route('coordinator.students.show', $otherDepartment))->assertForbidden();
    $this->get(route('coordinator.students.show', $noDepartment))->assertForbidden();
});

it('shows the department on the dashboard, profile, users list and department list, and the coordinator cannot change it', function () {
    $d = departmentSetup();
    $coordinatorUser = departmentCoordinator($d, $d['bsit']);

    $this->actingAs($coordinatorUser)->get(route('coordinator.dashboard'))
        ->assertOk()
        ->assertSee('Coordinator – BS Information Technology (BSIT)');
    $this->get(route('coordinator.profile.edit'))
        ->assertOk()
        ->assertSee('value="BS Information Technology (BSIT)" disabled', false)
        ->assertDontSee('name="department"', false);

    // Sending another department with the profile form changes nothing.
    $this->put(route('coordinator.profile.update'), [
        'name' => 'Maria Santos', 'email' => $coordinatorUser->email, 'phone' => '09171234567',
        'position' => 'OJT Coordinator', 'office_address' => 'Main Building', 'program_id' => $d['bsba']->id,
    ])->assertSessionHasNoErrors();
    expect($coordinatorUser->coordinator->fresh()->program_id)->toBe($d['bsit']->id);

    $this->actingAs($d['admin'])->get(route('admin.users.index', ['role' => 'coordinator']))
        ->assertOk()
        ->assertSee('<th scope="col">Department</th>', false)
        ->assertSee('BS Information Technology (BSIT)');
    $this->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('No coordinator assigned'); // BSBA
});

it('lets an admin reassign a coordinator only to a program without one, and frees a place when the coordinator is deleted or changes role', function () {
    $d = departmentSetup();
    $first = departmentCoordinator($d, $d['bsit'], 'First Coordinator');
    $second = departmentCoordinator($d, $d['bsba'], 'Second Coordinator');
    $bscs = Program::create(['name' => 'BS Computer Science', 'code' => 'BSCS', 'required_internship_hours' => 486]);
    $update = fn (User $user, array $changes) => $this->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'role' => 'coordinator', 'is_active' => 1, ...$changes,
    ]);
    $this->actingAs($d['admin']);

    $update($second, ['program_id' => $d['bsit']->id])
        ->assertSessionHasErrors(['program_id' => 'This program already has a coordinator.']);
    $update($second, ['program_id' => $bscs->id])->assertSessionHasNoErrors();
    expect($second->coordinator->fresh()->program_id)->toBe($bscs->id);

    // Deleting the first coordinator frees a place in BSIT for the second.
    $this->delete(route('admin.users.destroy', $first))->assertRedirect();
    expect(User::find($first->id))->toBeNull()->and(Coordinator::where('user_id', $first->id)->exists())->toBeFalse();
    $update($second, ['program_id' => $d['bsit']->id])->assertSessionHasNoErrors();

    // No longer a coordinator: a place is free again (BSIT, BSBA and BSCS have none).
    $update($second, ['role' => 'employer'])->assertSessionHasNoErrors();
    expect($second->coordinator->fresh()->program_id)->toBeNull()
        ->and(Program::whereDoesntHave('coordinator')->count())->toBe(3)
        ->and(Coordinator::departmentHasRoom($d['bsit']->id))->toBeTrue();
});
