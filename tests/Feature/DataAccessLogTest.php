<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\DataAccessLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/** A plain route that the middleware treats like a student profile page. */
function daRoutes(): void
{
    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/_test/students/{student}', fn (Student $student) => 'profile of '.$student->id)->name('test.student');
        Route::get('/_test/forbidden/{student}', fn (Student $student) => abort(403))->name('test.forbidden');
        Route::get('/_test/evidence/{type}/{id}', fn () => 'file')->name('test.evidence');
    });
    Route::getRoutes()->refreshNameLookups();
    config(['security.access_log.routes' => config('security.access_log.routes') + [
        'test.student' => 'profile', 'test.forbidden' => 'profile', 'test.evidence' => 'file',
    ]]);
}

it('records a company opening an intern, once per 10 minutes', function () {
    $company = svCompany();
    $placement = svPlacement($company['employer']);

    $this->actingAs($company['hrUser'])->get(route('employer.interns.show', $placement))->assertOk();
    $this->get(route('employer.interns.show', $placement))->assertOk();

    expect(DataAccessLog::count())->toBe(1);
    $row = DataAccessLog::first();
    expect($row->user_id)->toBe($company['hrUser']->id)
        ->and($row->viewer_role)->toBe('employer')
        ->and($row->student_id)->toBe($placement->student_id)
        ->and($row->action)->toBe('ojt_record')
        ->and($row->subject_type)->toBe('Placement')
        ->and($row->route)->toBe('employer.interns.show');

    $this->travel(11)->minutes();
    $this->get(route('employer.interns.show', $placement))->assertOk();
    expect(DataAccessLog::count())->toBe(2);
});

it('records supervisors and admins, but not the student, refused requests, or other pages', function () {
    daRoutes();
    $company = svCompany();
    $supervisor = svSupervisor($company['employer']);
    $placement = svPlacement($company['employer'], $supervisor);
    $student = $placement->student;

    $this->actingAs($supervisor)->get(route('supervisor.interns.show', $placement))->assertOk();
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get(route('test.student', $student))->assertOk();
    expect(DataAccessLog::pluck('viewer_role')->all())->toEqualCanonicalizing(['supervisor', 'admin']);

    // The student opening their own data, a refused request and an unlisted page leave nothing.
    $this->actingAs($student->user)->get(route('test.student', $student))->assertOk();
    $this->actingAs($company['hrUser'])->get(route('test.forbidden', $student))->assertForbidden();
    $this->get(route('employer.dashboard'));
    expect(DataAccessLog::count())->toBe(2);
});

it('finds the student behind a verification file named by type and id', function () {
    daRoutes();
    $student = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student])->id, 'profile_completion' => 0]);
    $certificate = Certificate::create(['student_id' => $student->id, 'title' => 'CCNA', 'file_path' => 'certificates/x.pdf']);

    $this->actingAs(User::factory()->create(['role' => UserRole::Coordinator]))->get(route('test.evidence', ['certificates', $certificate->id]))->assertOk();

    $row = DataAccessLog::sole();
    expect($row->student_id)->toBe($student->id)->and($row->subject_type)->toBe('Certificate')->and($row->subject_id)->toBe($certificate->id);
});

it('shows the records to admins only, with filters', function () {
    $company = svCompany();
    $placement = svPlacement($company['employer']);
    $placement->student->user->update(['name' => 'Hazel Villaflor']);
    $this->actingAs($company['hrUser'])->get(route('employer.interns.show', $placement))->assertOk();

    $this->get(route('admin.data-access'))->assertForbidden();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.data-access'))->assertOk()
        ->assertSee('Hazel Villaflor')->assertSee($company['hrUser']->name)->assertSee('OJT record')->assertSee('HR / company');
    $this->get(route('admin.data-access', ['student' => 'Villaflor']))->assertOk()->assertSee('Hazel Villaflor');
    $this->get(route('admin.data-access', ['student' => 'Nobody Here']))->assertOk()->assertDontSee('Hazel Villaflor');
    $this->get(route('admin.data-access', ['action' => 'resume']))->assertOk()->assertDontSee('Hazel Villaflor');
    $this->get(route('admin.data-access', ['action' => 'not-an-action']))->assertSessionHasErrors('action');
});

it('removes records older than a year', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $old = DataAccessLog::create(['user_id' => $user->id, 'viewer_role' => 'admin', 'action' => 'profile']);
    $old->forceFill(['created_at' => now()->subDays(400)])->save();
    $recent = DataAccessLog::create(['user_id' => $user->id, 'viewer_role' => 'admin', 'action' => 'profile']);

    $this->artisan('logs:prune')->expectsOutputToContain('Removed 1 data access entry')->assertSuccessful();
    expect(DataAccessLog::pluck('id')->all())->toBe([$recent->id]);
});

it('only lists routes that exist', function () {
    foreach (array_keys(config('security.access_log.routes')) as $name) {
        expect(Route::has($name))->toBeTrue("Route [{$name}] in security.access_log.routes does not exist.");
    }
    expect(array_diff(array_values(config('security.access_log.routes')), array_keys(DataAccessLog::ACTIONS)))->toBe([]);
});
