<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Http\Middleware\EnsureCompanyIsVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function partnerCompany(bool $verified): array
{
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $user->id,
        'company_name' => 'Acme Corp',
        'contact_person' => 'HR',
        'verified_at' => $verified ? now() : null,
    ]);

    return [$user, $employer];
}

function partnerStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create([
        'user_id' => $user->id,
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
        ...$attributes,
    ]);
}

it('lets an unverified company manage its profile but not post, browse, or invite', function () {
    [$user, $employer] = partnerCompany(verified: false);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Old post', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $student = partnerStudent();
    $this->actingAs($user);

    $this->get(route('employer.dashboard'))->assertOk()->assertSee('waiting for admin verification');
    $this->get(route('employer.profile.edit'))->assertOk()->assertSee('Pending admin verification');
    $this->get(route('employer.internships.index'))->assertOk();
    $this->get(route('employer.applicants.index'))->assertOk();

    $this->get(route('employer.internships.create'))->assertRedirect(route('employer.dashboard'))->assertSessionHas('error', EnsureCompanyIsVerified::MESSAGE);
    $this->post(route('employer.internships.store'), ['title' => 'New'])->assertRedirect(route('employer.dashboard'));
    $this->get(route('employer.internships.edit', $internship))->assertRedirect(route('employer.dashboard'));
    $this->post(route('employer.internships.close', $internship))->assertRedirect(route('employer.dashboard'));
    $this->get(route('employer.students.index'))->assertRedirect(route('employer.dashboard'));
    $this->get(route('employer.students.show', $student))->assertRedirect(route('employer.dashboard'));
    $this->postJson(route('employer.invitations.store'), ['student_id' => $student->id, 'internship_id' => $internship->id])
        ->assertForbidden()->assertJsonPath('message', EnsureCompanyIsVerified::MESSAGE);
    expect(Internship::count())->toBe(1);
});

it('lets an admin verify and unverify a company, notifying it when verified', function () {
    [$user, $employer] = partnerCompany(verified: false);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Verify company');
    $this->post(route('admin.users.verify-company', $user))->assertRedirect();
    $employer->refresh();
    expect($employer->isVerified())->toBeTrue()
        ->and($employer->verified_by_user_id)->toBe($admin->id)
        ->and(AppNotification::where('user_id', $user->id)->where('type', 'company_verified')->exists())->toBeTrue();

    $this->actingAs($user)->get(route('employer.internships.create'))->assertOk();
    $this->get(route('employer.students.index'))->assertOk();

    $this->actingAs($admin)->post(route('admin.users.unverify-company', $user))->assertRedirect();
    expect($employer->fresh()->isVerified())->toBeFalse();
    // A fresh user, as on a real request (the old instance still holds the verified company).
    $this->actingAs($user->fresh())->get(route('employer.internships.create'))->assertRedirect(route('employer.dashboard'));
});

it('does not let a company verify itself', function () {
    [$user, $employer] = partnerCompany(verified: false);

    $this->actingAs($user)->post(route('admin.users.verify-company', $user))->assertForbidden();
    expect($employer->fresh()->isVerified())->toBeFalse();
});

it('marks companies that already existed as verified when migrating', function () {
    [, $employer] = partnerCompany(verified: false);
    $migration = require database_path('migrations/2026_11_02_000001_add_company_verification_and_shortlists.php');

    $migration->down();
    $migration->up();

    expect(DB::table('employers')->where('id', $employer->id)->value('verified_at'))->not->toBeNull();
});

it('shows the Verified company badge to students only for verified companies', function () {
    [, $verified] = partnerCompany(verified: true);
    [, $pending] = partnerCompany(verified: false);
    $good = Internship::create(['employer_id' => $verified->id, 'title' => 'Verified post', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $unverifiedPost = Internship::create(['employer_id' => $pending->id, 'title' => 'Pending post', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $student = partnerStudent();

    $this->actingAs($student->user)->get(route('student.internships.show', $good))->assertOk()->assertSee('Verified company');
    $this->get(route('student.internships.show', $unverifiedPost))->assertOk()->assertDontSee('Verified company');
});

it('keeps a private shortlist the company can filter by', function () {
    [$user, $employer] = partnerCompany(verified: true);
    $picked = partnerStudent();
    $other = partnerStudent();
    $hidden = partnerStudent(['discoverable' => false]);
    $this->actingAs($user);

    $this->post(route('employer.students.shortlist.store', $picked))->assertRedirect();
    $this->post(route('employer.students.shortlist.store', $picked))->assertRedirect();
    expect($employer->shortlistedStudents()->count())->toBe(1);

    $this->get(route('employer.students.index', ['shortlisted' => 1]))
        ->assertOk()
        ->assertSee($picked->user->name)
        ->assertDontSee($other->user->name)
        ->assertSee('Shortlisted');

    // Students who cannot be browsed cannot be shortlisted.
    $this->post(route('employer.students.shortlist.store', $hidden))->assertNotFound();

    // Another company's shortlist is separate.
    [$otherUser, $otherEmployer] = partnerCompany(verified: true);
    $this->actingAs($otherUser)->get(route('employer.students.index', ['shortlisted' => 1]))->assertDontSee($picked->user->name);
    expect($otherEmployer->shortlistedStudents()->count())->toBe(0);

    $this->actingAs($user)->delete(route('employer.students.shortlist.destroy', $picked))->assertRedirect();
    expect($employer->shortlistedStudents()->count())->toBe(0);
    // The student is never told.
    expect(AppNotification::where('user_id', $picked->user_id)->exists())->toBeFalse();
});

it('shows the intern\'s OJT coordinator with a way to message them', function () {
    $institution = Institution::create(['name' => 'St. Cecilia\'s College-Cebu']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator, 'name' => 'Prof. Santos']);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    [$user, $employer] = partnerCompany(verified: true);
    $student = partnerStudent(['institution_id' => $institution->id]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);
    $placement = app(\App\Services\PlacementService::class)->openForApplication($application);

    $this->actingAs($user)
        ->get(route('employer.interns.show', $placement))
        ->assertOk()
        ->assertSee('OJT coordinator')
        ->assertSee('Prof. Santos')
        ->assertSee(route('messages.show', $coordinatorUser), false);
    $this->get(route('employer.applicants.show', $application))->assertOk()->assertSee('Prof. Santos');
    $this->get(route('messages.show', $coordinatorUser))->assertOk();
});
