<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/** A company (HR account) with a placement and, optionally, supervisors; plus another company's HR account. */
function svCompany(string $name = 'Applus Labs'): array
{
    $hrUser = User::factory()->create(['role' => UserRole::Employer, 'name' => $name.' HR']);
    $employer = Employer::create(['user_id' => $hrUser->id, 'company_name' => $name, 'contact_person' => $name.' HR', 'verified_at' => now()]);

    return compact('hrUser', 'employer');
}

function svSupervisor(Employer $employer, string $name = 'Sam Supervisor'): User
{
    $user = User::factory()->create(['role' => UserRole::Supervisor, 'name' => $name]);
    Supervisor::create(['user_id' => $user->id, 'employer_id' => $employer->id, 'position' => 'Senior Engineer']);

    return $user;
}

function svPlacement(Employer $employer, ?User $supervisor = null): Placement
{
    $institution = Institution::firstOrCreate(['name' => 'Supervisor College']);
    $student = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student])->id, 'institution_id' => $institution->id, 'program' => 'BSIT']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Trainee', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);

    return Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id, 'internship_id' => $internship->id,
        'start_date' => '2026-02-02', 'required_hours' => 500, 'status' => Placement::STATUS_ACTIVE, 'supervisor_user_id' => $supervisor?->id,
    ]);
}

it('has a supervisor role with its own labels and home page', function () {
    expect(UserRole::from('supervisor'))->toBe(UserRole::Supervisor)
        ->and(UserRole::Supervisor->label())->toBe('Supervisor')
        ->and(UserRole::Supervisor->dashboardRoute())->toBe('supervisor.dashboard')
        ->and(UserRole::Employer->label())->toBe('HR / Company') // the stored value stays "employer"
        ->and(UserRole::Employer->value)->toBe('employer')
        ->and(UserRole::Supervisor->ojtRoutePrefix())->toBe('supervisor')
        ->and(UserRole::Employer->ojtRoutePrefix())->toBe('employer')
        ->and(UserRole::assignable())->not->toContain(UserRole::Supervisor); // only HR creates supervisors
});

it('lets the HR account supervise only while no supervisor is assigned, and the assigned supervisor only their own interns', function () {
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $sam = svSupervisor($a['employer'], 'Sam');
    $kim = svSupervisor($a['employer'], 'Kim');
    $mine = svPlacement($a['employer'], $sam);
    $unassigned = svPlacement($a['employer']);
    $can = fn (User $user, string $ability, Placement $placement) => Gate::forUser($user)->allows($ability, $placement);

    // Assigned: only Sam may supervise. HR can still look, but not act.
    expect($can($sam, 'supervise', $mine))->toBeTrue()
        ->and($can($a['hrUser'], 'supervise', $mine))->toBeFalse()
        ->and($can($a['hrUser'], 'view', $mine))->toBeTrue()
        ->and($can($kim, 'supervise', $mine))->toBeFalse()   // same company, other intern
        ->and($can($kim, 'view', $mine))->toBeFalse()
        ->and($can($b['hrUser'], 'supervise', $mine))->toBeFalse()
        ->and($can($b['hrUser'], 'view', $mine))->toBeFalse();

    // Nobody assigned: HR covers it, and supervisors have no access.
    expect($can($a['hrUser'], 'supervise', $unassigned))->toBeTrue()
        ->and($can($sam, 'supervise', $unassigned))->toBeFalse()
        ->and($can($sam, 'view', $unassigned))->toBeFalse();
});

it('hands the intern back to the HR account when the supervisor is deactivated, and back again on reactivation', function () {
    $a = svCompany();
    $sam = svSupervisor($a['employer']);
    $placement = svPlacement($a['employer'], $sam);

    expect($placement->activeSupervisor()?->is($sam))->toBeTrue()->and($placement->supervisingUser()?->is($sam))->toBeTrue();

    $sam->update(['is_active' => false]);
    $placement = $placement->fresh();
    expect($placement->activeSupervisor())->toBeNull()
        ->and($placement->supervisingUser()?->is($a['hrUser']))->toBeTrue()
        ->and(Gate::forUser($a['hrUser'])->allows('supervise', $placement))->toBeTrue()
        ->and(Gate::forUser($sam)->allows('supervise', $placement))->toBeFalse();

    $sam->update(['is_active' => true]);
    expect($placement->fresh()->activeSupervisor()?->is($sam))->toBeTrue();
});

it('ignores a supervisor who belongs to another company', function () {
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $outsider = svSupervisor($b['employer']);
    $placement = svPlacement($a['employer'], $outsider); // wrongly pointed at another company's supervisor

    expect($placement->activeSupervisor())->toBeNull()
        ->and(Gate::forUser($outsider)->allows('supervise', $placement))->toBeFalse()
        ->and(Gate::forUser($a['hrUser'])->allows('supervise', $placement))->toBeTrue();
});

it('creates the supervisors table with a unique user, and an intern survives its supervisor account being deleted', function () {
    $a = svCompany();
    $sam = svSupervisor($a['employer']);
    $placement = svPlacement($a['employer'], $sam);

    expect(fn () => Supervisor::create(['user_id' => $sam->id, 'employer_id' => $a['employer']->id]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
    expect($a['employer']->supervisors()->count())->toBe(1)->and($sam->supervisor->employer->is($a['employer']))->toBeTrue();

    $sam->forceDelete();
    $placement = $placement->fresh();
    expect(Placement::find($placement->id))->not->toBeNull()
        ->and($placement->activeSupervisor())->toBeNull()
        ->and(Gate::forUser($a['hrUser'])->allows('supervise', $placement))->toBeTrue(); // HR covers it again
});
