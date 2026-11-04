<?php

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\ContentReport;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\Message;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentSkill;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

function adminCompany(): array
{
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $user->id, 'company_name' => 'Shady Corp', 'contact_person' => 'HR', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Too Good To Be True', 'description' => 'Pay a fee to apply', 'work_setup' => 'remote', 'status' => 'open']);

    return [$user, $employer, $internship];
}

// 1. Audit and security log
it('records failed, blocked, and successful sign-ins as security events', function () {
    $user = User::factory()->create(['email' => 'ana@example.test', 'password' => Hash::make('Secret123!')]);

    $this->post(route('login'), ['login' => 'nobody@example.test', 'password' => 'x']);
    $this->post(route('login'), ['login' => 'ana@example.test', 'password' => 'wrong']);
    $this->post(route('login'), ['login' => 'ana@example.test', 'password' => 'Secret123!']);

    expect(SystemLog::where('action', 'login_failed')->count())->toBe(2)
        ->and(SystemLog::where('action', 'login')->where('user_id', $user->id)->exists())->toBeTrue()
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('filters the log by type, action, user, and date, and exports CSV', function () {
    $admin = adminUser();
    $other = User::factory()->create(['name' => 'Zed Target']);
    SystemLog::create(['user_id' => $other->id, 'action' => 'login_failed', 'ip_address' => '10.0.0.9']);
    SystemLog::create(['user_id' => $other->id, 'action' => 'eligibility_rejected', 'details' => ['student_id' => 5]]);
    $old = SystemLog::create(['user_id' => $other->id, 'action' => 'login', 'ip_address' => '10.0.0.1']);
    $old->forceFill(['created_at' => now()->subDays(40)])->save();
    $this->actingAs($admin);

    $this->get(route('admin.logs', ['type' => 'security']))->assertOk()->assertSee('>login_failed</span>', false)->assertDontSee('>eligibility_rejected</span>', false);
    $this->get(route('admin.logs', ['type' => 'audit']))->assertOk()->assertSee('>eligibility_rejected</span>', false)->assertDontSee('>login_failed</span>', false);
    $this->get(route('admin.logs', ['q' => 'Zed', 'from' => now()->subDays(7)->toDateString()]))->assertOk()->assertSee('eligibility_rejected')->assertDontSee('10.0.0.1');

    $csv = $this->get(route('admin.logs.export', ['type' => 'security']));
    $csv->assertOk();
    expect($csv->streamedContent())->toContain('login_failed')->not->toContain('eligibility_rejected');
});

it('flags repeated failed sign-ins from one IP', function () {
    foreach (range(1, 5) as $i) {
        SystemLog::create(['action' => 'login_failed', 'ip_address' => '203.0.113.7']);
    }

    $this->actingAs(adminUser())->get(route('admin.logs', ['type' => 'security']))
        ->assertOk()->assertSee('Repeated failed sign-ins')->assertSee('203.0.113.7: 5 failures');
});

it('prunes log entries older than one year', function () {
    $old = SystemLog::create(['action' => 'login']);
    $old->forceFill(['created_at' => now()->subDays(366)])->save();
    $recent = SystemLog::create(['action' => 'login']);

    $this->artisan('logs:prune')->assertSuccessful();

    expect(SystemLog::find($old->id))->toBeNull()->and(SystemLog::find($recent->id))->not->toBeNull();
});

it('keeps the log admin-only', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Coordinator]))->get(route('admin.logs'))->assertForbidden();
});

// 2. Suspend accounts
it('suspends an account with a reason and end date, blocks sign-in, and lifts it', function () {
    $admin = adminUser();
    $user = User::factory()->create(['email' => 'sus@example.test', 'password' => Hash::make('Secret123!')]);
    $until = now('Asia/Manila')->addDays(3)->format('Y-m-d\TH:i');

    $this->actingAs($admin)->post(route('admin.users.suspend', $user), ['suspended_until' => $until])
        ->assertSessionHasErrors('suspension_reason');
    $this->post(route('admin.users.suspend', $user), ['suspension_reason' => 'Spamming companies', 'suspended_until' => $until])->assertRedirect();
    expect($user->fresh()->isSuspended())->toBeTrue();
    $this->assertDatabaseHas('system_logs', ['action' => 'account_suspended']);

    auth()->logout();
    $this->post(route('login'), ['login' => 'sus@example.test', 'password' => 'Secret123!'])
        ->assertSessionHasErrors(['login' => $user->fresh()->suspensionMessage()]);
    expect($user->fresh()->suspensionMessage())->toContain('Spamming companies')->toContain('until');
    $this->assertGuest();

    $this->actingAs($admin)->post(route('admin.users.lift-suspension', $user))->assertRedirect();
    expect($user->fresh()->isSuspended())->toBeFalse();
    $this->assertDatabaseHas('system_logs', ['action' => 'account_suspension_lifted']);
});

it('signs out a suspended user on their next request and ends the suspension automatically', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->forceFill(['suspended_at' => now(), 'suspended_until' => now()->addHour(), 'suspension_reason' => 'Review'])->save();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors('login');
    $this->assertGuest();

    $user->forceFill(['suspended_until' => now()->subMinute()])->save();
    expect($user->fresh()->isSuspended())->toBeFalse();
});

it('does not let an admin suspend themselves', function () {
    $admin = adminUser();
    adminUser();

    $this->actingAs($admin)->post(route('admin.users.suspend', $admin), ['suspension_reason' => 'x'])->assertSessionHas('error', 'You cannot suspend your own account.');
    expect($admin->fresh()->isSuspended())->toBeFalse();
});

// 3. Institutions
it('adds, verifies, and unverifies institutions; only verified ones can be picked for new accounts', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $this->post(route('admin.institutions.store'), ['name' => 'New College'])->assertRedirect();
    $institution = Institution::where('name', 'New College')->sole();
    expect($institution->isVerified())->toBeFalse();
    $this->get(route('admin.users.create'))->assertOk()->assertDontSee('value="'.$institution->id.'"', false);
    $this->post(route('admin.users.store'), ['name' => 'Coord', 'email' => 'c@example.test', 'role' => 'coordinator', 'institution_id' => $institution->id, 'password' => 'Secret123!', 'password_confirmation' => 'Secret123!'])
        ->assertSessionHasErrors('institution_id');

    $this->post(route('admin.institutions.verify', $institution))->assertRedirect();
    expect($institution->fresh()->isVerified())->toBeTrue();
    $this->get(route('admin.users.create'))->assertSee('value="'.$institution->id.'"', false);
    $this->assertDatabaseHas('system_logs', ['action' => 'institution_verified']);

    $this->post(route('admin.institutions.unverify', $institution))->assertRedirect();
    expect($institution->fresh()->isVerified())->toBeFalse();
    $this->post(route('admin.institutions.store'), ['name' => 'New College'])->assertSessionHasErrors(['name' => 'An institution with this name already exists.']);
});

// 4. Reports and moderation
it('lets users report a posting and admins hide it, notifying reporters and the owner', function () {
    [$companyUser, , $internship] = adminCompany();
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $studentUser->id]);
    $admin = adminUser();

    $this->actingAs($studentUser)->post(route('content-reports.store'), ['type' => 'internship', 'id' => $internship->id, 'reason' => 'scam', 'details' => 'Asks for a fee'])
        ->assertSessionHas('success', 'Thanks for reporting. An administrator will review it.');
    $this->post(route('content-reports.store'), ['type' => 'internship', 'id' => $internship->id, 'reason' => 'scam'])
        ->assertSessionHas('success', 'You already reported this. An administrator will review it.');
    $report = ContentReport::sole();
    expect(AppNotification::where('user_id', $admin->id)->where('type', 'content_report')->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()->assertSee('Too Good To Be True');
    $this->get(route('admin.moderation.show', $report))->assertOk()->assertSee('Asks for a fee')->assertSee('Hide the content');
    $this->post(route('admin.moderation.resolve', $report), ['resolution' => 'hide'])->assertRedirect(route('admin.moderation.index'));

    expect($internship->fresh()->hidden_at)->not->toBeNull()
        ->and($internship->fresh()->isAcceptingApplications())->toBeFalse()
        ->and(Internship::query()->open()->count())->toBe(0)
        ->and($report->fresh()->status)->toBe(ContentReport::STATUS_ACTIONED)
        ->and(AppNotification::where('user_id', $studentUser->id)->where('title', 'Your report was reviewed')->exists())->toBeTrue()
        ->and(AppNotification::where('user_id', $companyUser->id)->where('title', 'Content removed by an administrator')->exists())->toBeTrue();
    $this->assertDatabaseHas('system_logs', ['action' => 'report_resolved']);
});

it('warns or suspends the owner of reported content, and dismisses reports', function () {
    [$companyUser, $employer] = adminCompany();
    $reporter = User::factory()->create(['role' => UserRole::Coordinator]);
    $admin = adminUser();
    $first = ContentReport::create(['reporter_user_id' => $reporter->id, 'reportable_type' => 'company', 'reportable_id' => $employer->id, 'reason' => 'misleading', 'status' => 'open']);

    $this->actingAs($admin)->post(route('admin.moderation.resolve', $first), ['resolution' => 'warn'])->assertSessionHasErrors('warning');
    $this->post(route('admin.moderation.resolve', $first), ['resolution' => 'warn', 'warning' => 'Fix your company description.'])->assertRedirect();
    expect(AppNotification::where('user_id', $companyUser->id)->where('message', 'Fix your company description.')->exists())->toBeTrue();

    $second = ContentReport::create(['reporter_user_id' => $reporter->id, 'reportable_type' => 'company', 'reportable_id' => $employer->id, 'reason' => 'scam', 'status' => 'open']);
    $this->post(route('admin.moderation.resolve', $second), ['resolution' => 'suspend', 'suspension_reason' => 'Fraudulent postings'])->assertRedirect();
    expect($companyUser->fresh()->isSuspended())->toBeTrue();

    $third = ContentReport::create(['reporter_user_id' => $reporter->id, 'reportable_type' => 'company', 'reportable_id' => $employer->id, 'reason' => 'spam', 'status' => 'open']);
    $this->post(route('admin.moderation.resolve', $third), ['resolution' => 'dismiss'])->assertRedirect();
    expect($third->fresh()->status)->toBe(ContentReport::STATUS_DISMISSED);
});

it('lets the receiver report a message, which an admin can hide', function () {
    $sender = User::factory()->create(['role' => UserRole::Coordinator]);
    $receiver = User::factory()->create(['role' => UserRole::Employer]);
    $stranger = User::factory()->create(['role' => UserRole::Employer]);
    $message = Message::create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id, 'body' => 'Rude words']);

    $this->actingAs($stranger)->post(route('content-reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'harassment'])->assertNotFound();
    $this->actingAs($sender)->post(route('content-reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'harassment'])->assertNotFound();
    $this->actingAs($receiver)->post(route('content-reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'harassment'])->assertRedirect();

    $this->actingAs(adminUser())->post(route('admin.moderation.resolve', ContentReport::sole()), ['resolution' => 'hide'])->assertRedirect();
    expect($message->fresh()->isHidden())->toBeTrue()->and($message->fresh()->displayBody())->toBe('This message was removed by an administrator.');
});

it('keeps reports visible to admins only', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Coordinator]))->get(route('admin.moderation.index'))->assertForbidden();
});

// 5. Usage and health
it('shows usage numbers and health checks', function () {
    User::factory()->create(['last_login_at' => now()->subDays(2)]);
    SystemLog::create(['action' => 'login']);

    $this->actingAs(adminUser())->get(route('admin.health'))
        ->assertOk()
        ->assertSee('Active (7 days)')
        ->assertSee('Sign-ins, last 14 days')
        ->assertSee('data-health-check="database"', false)
        ->assertSee('data-health-check="scheduler"', false)
        ->assertSee('Has not run yet');
});

// 6. Skill tag management
it('renames, merges, and deletes skill tags', function () {
    $admin = adminUser();
    $js = SkillTag::create(['name' => 'JS Coding']);
    $javascript = SkillTag::firstOrCreate(['name_normalized' => 'javascript'], ['name' => 'JavaScript']);
    $both = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student])->id]);
    $onlyJs = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student])->id]);
    StudentSkill::create(['student_id' => $both->id, 'skill_tag_id' => $js->id, 'level' => 'advanced']);
    StudentSkill::create(['student_id' => $both->id, 'skill_tag_id' => $javascript->id]);
    StudentSkill::create(['student_id' => $onlyJs->id, 'skill_tag_id' => $js->id]);
    $this->actingAs($admin);

    $this->get(route('admin.skill-tags.index'))->assertOk()->assertSee('JS Coding')->assertSee('data-confirm', false);

    $this->put(route('admin.skill-tags.update', $js), ['name' => 'javascript'])->assertSessionHas('error');
    $this->put(route('admin.skill-tags.update', $js), ['name' => 'JS'])->assertSessionHas('success');
    expect($js->fresh()->name)->toBe('JS');

    $this->post(route('admin.skill-tags.merge', $js), ['target_id' => $javascript->id])->assertSessionHas('success');
    expect(SkillTag::find($js->id))->toBeNull()
        ->and(StudentSkill::where('skill_tag_id', $javascript->id)->count())->toBe(2)
        ->and(StudentSkill::where('student_id', $both->id)->sole()->level?->value)->toBe('advanced');
    $this->assertDatabaseHas('system_logs', ['action' => 'skill_tags_merged']);

    $this->delete(route('admin.skill-tags.destroy', $javascript))->assertSessionHas('success');
    expect(SkillTag::find($javascript->id))->toBeNull()->and(StudentSkill::count())->toBe(0);
    $this->assertDatabaseHas('system_logs', ['action' => 'skill_tag_deleted']);
});
