<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    config(['queue.default' => 'sync', 'mail.default' => 'log']);
});

/** One student who applied to two different companies, both at the interview stage. */
function twoCompanyApplications(): array
{
    $institution = Institution::create(['name' => 'Dup University', 'address' => 'Cebu', 'contact_email' => 'dup@example.edu']);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Dana Duplicate']);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id]);

    $make = function (string $company) use ($student) {
        $employerUser = User::factory()->create(['role' => UserRole::Employer]);
        $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => $company, 'contact_person' => 'HR']);
        $internship = Internship::create(['employer_id' => $employer->id, 'title' => "$company Intern", 'description' => 'Work.', 'status' => 'open']);
        $application = InternshipApplication::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => ApplicationStatus::Interview,
            'match_percentage' => 80,
        ]);

        return compact('employerUser', 'employer', 'internship', 'application');
    };

    return ['student' => $student, 'first' => $make('Alpha Corp'), 'second' => $make('Beta Corp')];
}

it('refuses to accept a student who already has an open placement, with a friendly message', function () {
    $f = twoCompanyApplications();

    $this->actingAs($f['first']['employerUser'])
        ->put(route('employer.applicants.status', $f['first']['application']), ['status' => 'accepted'])
        ->assertSessionHas('success');

    // The second company's accept is refused, and the application is NOT left as "accepted".
    $this->actingAs($f['second']['employerUser'])
        ->from(route('employer.applicants.show', $f['second']['application']))
        ->put(route('employer.applicants.status', $f['second']['application']), ['status' => 'accepted'])
        ->assertSessionHasErrors(['status' => 'Dana Duplicate already has an ongoing OJT placement with another company, so this application cannot be accepted. A student can only have one OJT at a time. Contact the student\'s coordinator if this needs to change.']);

    expect(Placement::where('student_id', $f['student']->id)->count())->toBe(1)
        ->and($f['second']['application']->fresh()->status)->toBe(ApplicationStatus::Interview);

    // The ajax form gets a 422 whose first error is shown under the button.
    $this->actingAs($f['second']['employerUser'])
        ->putJson(route('employer.applicants.status', $f['second']['application']), ['status' => 'accepted'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('hides Accepted and explains why on the second company\'s applicant page', function () {
    $f = twoCompanyApplications();
    $this->actingAs($f['first']['employerUser'])
        ->put(route('employer.applicants.status', $f['first']['application']), ['status' => 'accepted']);

    $this->actingAs($f['second']['employerUser'])
        ->get(route('employer.applicants.show', $f['second']['application']))
        ->assertOk()
        ->assertSee('already has an ongoing OJT placement with another company')
        ->assertDontSee('<option value="accepted"', false)
        ->assertSee('<option value="rejected"', false)
        // The other company is never named to this employer.
        ->assertDontSee('Alpha Corp');
});

it('still lets a student with a completed OJT be accepted again', function () {
    $f = twoCompanyApplications();
    $this->actingAs($f['first']['employerUser'])
        ->put(route('employer.applicants.status', $f['first']['application']), ['status' => 'accepted']);
    Placement::where('student_id', $f['student']->id)->update(['status' => Placement::STATUS_COMPLETED]);

    $this->actingAs($f['second']['employerUser'])
        ->put(route('employer.applicants.status', $f['second']['application']), ['status' => 'accepted'])
        ->assertSessionHas('success');

    expect(Placement::where('student_id', $f['student']->id)->count())->toBe(2);
});

it('is enforced by the database even when the app check is bypassed', function () {
    $f = twoCompanyApplications();
    $row = fn (array $company) => [
        'application_id' => $company['application']->id,
        'student_id' => $f['student']->id,
        'employer_id' => $company['employer']->id,
        'internship_id' => $company['internship']->id,
        'start_date' => now()->toDateString(),
        'required_hours' => 300,
        'status' => Placement::STATUS_ACTIVE,
    ];

    Placement::create($row($f['first']));
    expect(fn () => Placement::create($row($f['second'])))->toThrow(UniqueConstraintViolationException::class);

    // A soft-deleted placement no longer counts as open.
    Placement::where('student_id', $f['student']->id)->first()->delete();
    expect(Placement::create($row($f['second']))->exists)->toBeTrue();
});

it('lists each placed student once in the coordinator and admin placement lists', function () {
    $f = twoCompanyApplications();
    $this->actingAs($f['first']['employerUser'])
        ->put(route('employer.applicants.status', $f['first']['application']), ['status' => 'accepted']);
    $this->actingAs($f['second']['employerUser'])
        ->put(route('employer.applicants.status', $f['second']['application']), ['status' => 'accepted']);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $html = $this->actingAs($admin)->get(route('admin.placements.index'))->assertOk()->getContent();

    // One table row for the student (the refused attempt's flashed error is not a row).
    expect(Placement::count())->toBe(1)
        ->and(substr_count($html, '<strong>Dana Duplicate</strong>'))->toBe(1);
});
