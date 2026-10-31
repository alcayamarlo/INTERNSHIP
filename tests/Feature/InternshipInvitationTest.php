<?php

use App\Enums\ApplicationStatus;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\AppNotification;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

function inviteStudent(array $attributes = [], bool $withResume = true): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz '.Str::random(4), 'phone' => '09171234567']);
    $student = Student::create([
        'user_id' => $user->id,
        'program' => 'BS Information Technology',
        'year_level' => '4th Year',
        'major_subjects_passed' => true,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
        ...$attributes,
    ]);

    if ($withResume) {
        Storage::disk('private')->put('resumes/r'.$student->id.'.pdf', '%PDF-1.4');
        Resume::create(['student_id' => $student->id, 'file_path' => 'resumes/r'.$student->id.'.pdf', 'content' => [], 'generated_at' => now()]);
    }

    return $student;
}

function inviteEmployer(string $company = 'Acme Corp'): array
{
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $user->id, 'company_name' => $company, 'contact_person' => 'HR']);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Web Intern at '.$company,
        'description' => 'Build things.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    return [$user, $employer, $internship];
}

function sendInvite(User $employerUser, Student $student, Internship $internship, array $extra = [])
{
    return test()->actingAs($employerUser)->postJson(route('employer.invitations.store'), [
        'student_id' => $student->id,
        'internship_id' => $internship->id,
        ...$extra,
    ]);
}

it('shows employers only company-visible fields when browsing', function () {
    [$employerUser] = inviteEmployer();
    $student = inviteStudent([
        'civil_status' => 'Married', 'show_civil_status' => true,
        'religion' => 'Iglesia', 'show_religion' => true,
        'height_cm' => 171, 'show_height' => true,
        'address' => '45 Secret Street', 'emergency_contact_phone' => '09998887777',
    ]);
    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    $student->studentSkills()->create(['skill_tag_id' => $tag->id]);
    $hidden = inviteStudent(['discoverable' => false]);
    $noConsent = inviteStudent(['privacy_consent' => false, 'privacy_consent_at' => null]);

    $this->actingAs($employerUser);
    foreach ([route('employer.students.index'), route('employer.students.show', $student)] as $url) {
        $this->get($url)->assertOk()
            ->assertSee($student->user->name)
            ->assertSee('Python')
            ->assertDontSee($student->user->email)
            ->assertDontSee('09171234567')
            ->assertDontSee('Married')
            ->assertDontSee('Iglesia')
            ->assertDontSee('171')
            ->assertDontSee('45 Secret Street')
            ->assertDontSee('09998887777');
    }

    $this->get(route('employer.students.index'))
        ->assertDontSee($hidden->user->name)
        ->assertDontSee($noConsent->user->name);
    $this->get(route('employer.students.show', $hidden))->assertNotFound();
    $this->get(route('employer.students.show', $noConsent))->assertNotFound();
});

it('filters students by skill and hides students who do not meet the school rules', function () {
    AcademicSetting::current()->update(['require_fourth_year_standing' => true]);
    [$employerUser] = inviteEmployer();
    $match = inviteStudent();
    $other = inviteStudent();
    $thirdYear = inviteStudent(['year_level' => '3rd Year']);
    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    $match->studentSkills()->create(['skill_tag_id' => $tag->id]);

    $this->actingAs($employerUser)
        ->get(route('employer.students.index', ['skill' => 'python']))
        ->assertOk()
        ->assertSee($match->user->name)
        ->assertDontSee($other->user->name);
    $this->get(route('employer.students.index'))->assertDontSee($thirdYear->user->name);
});

it('sends an invitation with a bell notification and email to the student', function () {
    [$employerUser, $employer, $internship] = inviteEmployer();
    $student = inviteStudent();

    sendInvite($employerUser, $student, $internship, ['message' => 'We liked your Python projects.'])->assertCreated();

    $invitation = InternshipInvitation::sole();
    expect($invitation->status)->toBe(InvitationStatus::Pending)
        ->and($invitation->employer_id)->toBe($employer->id)
        ->and($invitation->message)->toBe('We liked your Python projects.')
        ->and($invitation->expires_at->between(now()->addDays(13), now()->addDays(15)))->toBeTrue();
    expect(AppNotification::where('user_id', $student->user_id)->where('message', "Acme Corp invited you to apply for {$internship->title}.")->exists())->toBeTrue();
});

it('does not let an employer invite for another employer\'s internship', function () {
    [$employerUser] = inviteEmployer();
    [, , $otherInternship] = inviteEmployer('Other Co');

    sendInvite($employerUser, inviteStudent(), $otherInternship)->assertForbidden();
    expect(InternshipInvitation::count())->toBe(0);
});

it('rejects duplicate, already-applied, closed, and expired-internship invitations', function () {
    [$employerUser, $employer, $internship] = inviteEmployer();
    $student = inviteStudent();

    sendInvite($employerUser, $student, $internship)->assertCreated();
    sendInvite($employerUser, $student, $internship)->assertUnprocessable()
        ->assertJsonValidationErrors(['internship_id' => 'You already invited this student to that internship.']);

    $applied = inviteStudent();
    InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $applied->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 0, 'applied_at' => now()]);
    sendInvite($employerUser, $applied, $internship)->assertUnprocessable()
        ->assertJsonValidationErrors(['internship_id' => 'This student already applied to that internship.']);

    $closed = Internship::create(['employer_id' => $employer->id, 'title' => 'Closed', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'closed']);
    $expired = Internship::create(['employer_id' => $employer->id, 'title' => 'Expired', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open', 'expires_at' => now()->subDay()]);
    foreach ([$closed, $expired] as $notOpen) {
        sendInvite($employerUser, inviteStudent(), $notOpen)->assertUnprocessable()->assertJsonValidationErrors('internship_id');
    }

    expect(InternshipInvitation::count())->toBe(1);
});

it('rejects invitations to students who opted out and enforces the daily limit', function () {
    config(['invitations.daily_limit' => 2]);
    [$employerUser, , $internship] = inviteEmployer();

    sendInvite($employerUser, inviteStudent(['discoverable' => false]), $internship)->assertUnprocessable()->assertJsonValidationErrors('student');

    sendInvite($employerUser, inviteStudent(), $internship)->assertCreated();
    sendInvite($employerUser, inviteStudent(), $internship)->assertCreated();
    sendInvite($employerUser, inviteStudent(), $internship)->assertUnprocessable()
        ->assertJsonValidationErrors(['internship_id' => 'You can send up to 2 invitations a day. Try again tomorrow.']);
});

it('accepts once: one Reviewed application, linked, logged, and both sides notified', function () {
    [$employerUser, , $internship] = inviteEmployer();
    $student = inviteStudent();
    sendInvite($employerUser, $student, $internship)->assertCreated();
    $invitation = InternshipInvitation::sole();

    $this->actingAs($student->user)
        ->post(route('student.invitations.accept', $invitation))
        ->assertRedirect()
        ->assertSessionHas('success', 'You accepted the invitation. The company will contact you about the interview. You can now message the company.');
    // A double click: the second accept returns the same application.
    $this->post(route('student.invitations.accept', $invitation))->assertRedirect();

    $application = InternshipApplication::sole();
    $invitation->refresh();
    expect($application->status)->toBe(ApplicationStatus::Reviewed)
        ->and($application->resume_id)->not->toBeNull()
        ->and($invitation->status)->toBe(InvitationStatus::Accepted)
        ->and($invitation->application_id)->toBe($application->id)
        ->and($invitation->responded_at)->not->toBeNull()
        ->and($application->statusHistory()->where('status', 'reviewed')->count())->toBe(1)
        ->and(AppNotification::where('user_id', $employerUser->id)->where('title', 'Invitation accepted')->count())->toBe(1)
        ->and(AppNotification::where('user_id', $student->user_id)->where('title', 'Invitation accepted')->count())->toBe(1);

    // Messaging opens, and contact details show on the applicant page.
    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSee($student->user->email)
        ->assertSee('Message Student');
    $this->postJson(route('messages.store', $student->user), ['body' => 'Interview next week?', 'submission_id' => (string) Str::uuid()])->assertOk();
});

it('asks for a resume first and creates nothing without one', function () {
    [$employerUser, , $internship] = inviteEmployer();
    $student = inviteStudent(withResume: false);
    sendInvite($employerUser, $student, $internship)->assertCreated();
    $invitation = InternshipInvitation::sole();

    $this->actingAs($student->user)
        ->post(route('student.invitations.accept', $invitation))
        ->assertRedirect(route('student.invitations.index'))
        ->assertSessionHas('error', 'Generate your resume first, then accept the invitation.');

    expect(InternshipApplication::count())->toBe(0)
        ->and($invitation->fresh()->status)->toBe(InvitationStatus::Pending);
});

it('declines without a reason and notifies the employer by bell only', function () {
    [$employerUser, , $internship] = inviteEmployer();
    $student = inviteStudent();
    sendInvite($employerUser, $student, $internship);
    $invitation = InternshipInvitation::sole();
    \Illuminate\Support\Facades\Queue::fake();

    $this->actingAs($student->user)->post(route('student.invitations.decline', $invitation))->assertRedirect();

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Declined)
        ->and(InternshipApplication::count())->toBe(0)
        ->and(AppNotification::where('user_id', $employerUser->id)->where('title', 'Invitation declined')->exists())->toBeTrue();
    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\SendNotification::class);
});

it('lets only the invited student see, accept, or decline an invitation', function () {
    [$employerUser, , $internship] = inviteEmployer();
    $student = inviteStudent();
    $other = inviteStudent();
    sendInvite($employerUser, $student, $internship);
    $invitation = InternshipInvitation::sole();

    $this->actingAs($other->user)->get(route('student.invitations.index'))->assertOk()->assertDontSee($internship->title);
    $this->post(route('student.invitations.accept', $invitation))->assertForbidden();
    $this->post(route('student.invitations.decline', $invitation))->assertForbidden();

    $this->actingAs($student->user)->get(route('student.invitations.index'))->assertOk()->assertSee($internship->title);
    expect($invitation->fresh()->status)->toBe(InvitationStatus::Pending);
});

it('expires old invitations and those for closed internships, reminds before expiry, and blocks accepting expired ones', function () {
    [$employerUser, $employer, $internship] = inviteEmployer();
    $old = inviteStudent();
    $closedFor = inviteStudent();
    $soon = inviteStudent();
    $closedInternship = Internship::create(['employer_id' => $employer->id, 'title' => 'Soon closed', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);

    $service = app(InvitationService::class);
    $oldInvite = $service->invite($employerUser, $old, $internship, null);
    $closedInvite = $service->invite($employerUser, $closedFor, $closedInternship, null);
    $soonInvite = $service->invite($employerUser, $soon, $internship, null);
    $oldInvite->update(['expires_at' => now()->subMinute()]);
    $closedInternship->update(['status' => 'closed']);
    $soonInvite->update(['expires_at' => now()->addDay()]);

    $this->artisan('invitations:expire')->assertSuccessful();
    $this->artisan('invitations:expire')->assertSuccessful();

    expect($oldInvite->fresh()->status)->toBe(InvitationStatus::Expired)
        ->and($closedInvite->fresh()->status)->toBe(InvitationStatus::Expired)
        ->and($soonInvite->fresh()->status)->toBe(InvitationStatus::Pending)
        ->and(AppNotification::where('user_id', $soon->user_id)->where('title', 'Invitation expiring soon')->count())->toBe(1)
        ->and(AppNotification::where('user_id', $old->user_id)->where('title', 'Invitation expired')->count())->toBe(1)
        ->and(AppNotification::where('user_id', $employerUser->id)->where('title', 'Invitation expired')->count())->toBe(2);

    $this->actingAs($old->user)->post(route('student.invitations.accept', $oldInvite))
        ->assertSessionHas('error', 'This invitation is no longer open.');
    expect(InternshipApplication::count())->toBe(0);
});

it('keeps employer and student messaging closed until the company shows interest', function () {
    [$employerUser, , $internship] = inviteEmployer();
    $student = inviteStudent();
    $payload = fn () => ['body' => 'Hi', 'submission_id' => (string) Str::uuid()];

    $this->actingAs($employerUser)->postJson(route('messages.store', $student->user), $payload())->assertForbidden();
    $this->get(route('messages.index'))->assertDontSee($student->user->name);
    $this->actingAs($student->user)->postJson(route('messages.store', $employerUser), $payload())->assertForbidden();

    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 0, 'applied_at' => now()]);
    $this->actingAs($employerUser)->postJson(route('messages.store', $student->user), $payload())->assertForbidden();

    $application->update(['status' => ApplicationStatus::Reviewed]);
    $this->postJson(route('messages.store', $student->user), $payload())->assertOk();
    $this->get(route('messages.index'))->assertSee($student->user->name);
    $this->actingAs($student->user)->postJson(route('messages.store', $employerUser), $payload())->assertOk();
});

it('lets a student turn off being found by companies', function () {
    [$employerUser] = inviteEmployer();
    $student = inviteStudent();

    $this->actingAs($student->user)
        ->put(route('student.profile.discoverable'), ['discoverable' => '0'])
        ->assertRedirect();
    expect($student->fresh()->discoverable)->toBeFalse();

    $this->actingAs($employerUser)->get(route('employer.students.index'))->assertDontSee($student->user->name);
});
