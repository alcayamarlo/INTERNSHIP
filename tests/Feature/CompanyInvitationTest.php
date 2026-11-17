<?php

use App\Enums\CompanyInvitationStatus;
use App\Enums\UserRole;
use App\Mail\CompanyInvitationMail;
use App\Models\AppNotification;
use App\Models\CompanyInvitation;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\User;
use App\Services\CompanyInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

function invitingCoordinator(string $email = 'coordinator@scc.test'): User
{
    $institution = Institution::firstOrCreate(['name' => 'St. Cecilia\'s College']);
    $user = User::factory()->create(['role' => UserRole::Coordinator, 'email' => $email, 'name' => 'Maria Santos']);
    Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id]);

    return $user;
}

function inviteCompany(User $coordinator, array $overrides = []): string
{
    test()->actingAs($coordinator)
        ->post(route('coordinator.companies.invitations.store'), [
            'company_name' => 'Applus Velosi Philippines Inc.',
            'address' => 'IT Park, Cebu City',
            'email' => 'hr@applus.test',
            'phone' => '09171234567',
            ...$overrides,
        ])
        ->assertRedirect(route('coordinator.companies.invitations.index'));

    return lastInvitationToken();
}

/** The raw token only exists in the emailed link. */
function lastInvitationToken(): string
{
    $url = null;
    Mail::assertSent(CompanyInvitationMail::class, function (CompanyInvitationMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    return basename(parse_url($url, PHP_URL_PATH));
}

function companyForm(array $overrides = []): array
{
    return [
        'company_name' => 'Applus Velosi Philippines Inc.',
        'address' => 'IT Park, Cebu City',
        'phone' => '09171234567',
        'industry' => 'Information Technology',
        'contact_person' => 'Ian Olmoguez',
        'description' => 'Engineering and inspection services.',
        'website' => 'https://applus.test',
        'password' => 'Secret#2026',
        'password_confirmation' => 'Secret#2026',
        ...$overrides,
    ];
}

it('lets a coordinator invite a company, storing only a hash of the emailed token', function () {
    $coordinator = invitingCoordinator();

    $token = inviteCompany($coordinator, ['email' => '  HR@Applus.TEST ']);

    $invitation = CompanyInvitation::sole();
    expect($invitation->reference)->toBe('CI-'.now()->format('Y').'-'.str_pad((string) $invitation->id, 5, '0', STR_PAD_LEFT))
        ->and($invitation->email)->toBe('hr@applus.test')
        ->and($invitation->status)->toBe(CompanyInvitationStatus::Sent)
        ->and($invitation->invited_by_user_id)->toBe($coordinator->id)
        ->and(strlen($token))->toBe(64)
        ->and($invitation->token_hash)->toBe(hash('sha256', $token))
        ->and($invitation->token_hash)->not->toBe($token);
    Mail::assertSent(CompanyInvitationMail::class, fn ($mail) => $mail->hasTo('hr@applus.test'));
    $this->assertDatabaseMissing('company_invitations', ['token_hash' => $token]);

    $this->get(route('coordinator.companies.invitations.index'))->assertOk()
        ->assertSee($invitation->reference)->assertSee('Applus Velosi Philippines Inc.')->assertSee('Sent');
});

it('prefills the company form from the link and creates a verified employer on accept without signing them in', function () {
    $coordinator = invitingCoordinator();
    $token = inviteCompany($coordinator);
    auth()->logout();

    $this->get(route('company-invitations.show', $token))->assertOk()
        ->assertSee('value="Applus Velosi Philippines Inc."', false)
        ->assertSee('IT Park, Cebu City')
        ->assertSee('value="hr@applus.test" readonly', false)
        ->assertSee('value="09171234567"', false);
    expect(CompanyInvitation::sole()->opened_at)->not->toBeNull();

    $this->post(route('company-invitations.accept', $token), companyForm())
        ->assertRedirect(route('home'))
        ->assertSessionHas('company_account_ready')
        ->assertSessionHas('signin_email', 'hr@applus.test');

    $user = User::where('email', 'hr@applus.test')->sole();
    $employer = Employer::where('user_id', $user->id)->sole();
    $invitation = CompanyInvitation::sole();
    expect($user->role)->toBe(UserRole::Employer)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->must_change_password)->toBeFalse()
        ->and($employer->verified_at)->not->toBeNull()
        ->and($employer->verified_by_user_id)->toBe($coordinator->id)
        ->and($employer->industry)->toBe('Information Technology')
        ->and($invitation->status)->toBe(CompanyInvitationStatus::Accepted)
        ->and($invitation->employer_id)->toBe($employer->id);
    // No automatic login: the company signs in from the landing page with the password it just set.
    $this->assertGuest();
    $this->post(route('login'), ['login' => 'hr@applus.test', 'password' => 'Secret#2026'])
        ->assertRedirect(route('employer.dashboard'));
    $this->get(route('employer.dashboard'))->assertOk();

    expect(AppNotification::where('user_id', $coordinator->id)->where('type', 'company_invitation')->exists())->toBeTrue();
    $this->actingAs($coordinator)->get(route('coordinator.companies.invitations.index'))->assertSee('Accepted');
});

it('refuses a used, tampered, or expired link', function () {
    $coordinator = invitingCoordinator();
    $token = inviteCompany($coordinator);
    auth()->logout();

    // Tampered: one character changed.
    $this->get(route('company-invitations.show', substr($token, 0, 63).($token[63] === 'a' ? 'b' : 'a')))
        ->assertNotFound()->assertSee('This invitation link is not valid');

    // Used: accepted once, then opened and submitted again.
    $this->post(route('company-invitations.accept', $token), companyForm())->assertRedirect(route('home'));
    auth()->logout();
    $this->get(route('company-invitations.show', $token))->assertStatus(410)->assertSee('already used');
    $this->post(route('company-invitations.accept', $token), companyForm(['contact_person' => 'Someone Else']))
        ->assertRedirect(route('company-invitations.show', $token));
    expect(User::where('email', 'hr@applus.test')->count())->toBe(1);

    // Expired: past its date, the page explains it and marks it expired.
    $other = inviteCompany($coordinator, ['email' => 'ops@another.test']);
    auth()->logout();
    $this->travel(config('company_invitations.expires_after_days') + 1)->days();
    $this->get(route('company-invitations.show', $other))->assertStatus(410)->assertSee('has expired')->assertSee('Maria Santos');
    expect(CompanyInvitation::where('email', 'ops@another.test')->sole()->status)->toBe(CompanyInvitationStatus::Expired);
    $this->post(route('company-invitations.accept', $other), companyForm())->assertRedirect(route('company-invitations.show', $other));
    expect(User::where('email', 'ops@another.test')->exists())->toBeFalse();
});

it('invalidates the old link when an invitation is resent', function () {
    $coordinator = invitingCoordinator();
    $oldToken = inviteCompany($coordinator);
    $invitation = CompanyInvitation::sole();
    Mail::fake(); // Forget the first email so the next token is the resent one.

    $this->post(route('coordinator.companies.invitations.resend', $invitation))
        ->assertRedirect(route('coordinator.companies.invitations.index'))
        ->assertSessionHas('success');
    $newToken = lastInvitationToken();
    auth()->logout();

    expect($newToken)->not->toBe($oldToken);
    $this->get(route('company-invitations.show', $oldToken))->assertNotFound();
    $this->post(route('company-invitations.accept', $oldToken), companyForm())->assertRedirect(route('company-invitations.show', $oldToken));
    $this->get(route('company-invitations.show', $newToken))->assertOk();
    $this->post(route('company-invitations.accept', $newToken), companyForm())->assertRedirect(route('home'));
});

it('lets an expired invitation be resent with a fresh expiry', function () {
    $coordinator = invitingCoordinator();
    inviteCompany($coordinator);
    $this->travel(20)->days();
    $invitation = CompanyInvitation::sole();
    expect($invitation->currentStatus())->toBe(CompanyInvitationStatus::Expired);

    $this->actingAs($coordinator)->post(route('coordinator.companies.invitations.resend', $invitation))->assertSessionHas('success');

    expect($invitation->fresh()->currentStatus())->toBe(CompanyInvitationStatus::Sent)
        ->and($invitation->fresh()->expires_at->isFuture())->toBeTrue();
});

it('rejects an email that already has an account or an open invitation', function () {
    $coordinator = invitingCoordinator();
    User::factory()->create(['email' => 'taken@company.test']);

    $this->actingAs($coordinator)->post(route('coordinator.companies.invitations.store'), ['company_name' => 'Taken Co', 'email' => 'taken@company.test'])
        ->assertSessionHasErrors(['email' => CompanyInvitationService::EMAIL_TAKEN]);

    inviteCompany($coordinator, ['email' => 'open@company.test']);
    $this->post(route('coordinator.companies.invitations.store'), ['company_name' => 'Again Co', 'address' => 'Cebu City', 'phone' => '09171234567', 'email' => 'open@company.test'])
        ->assertSessionHasErrors(['email' => CompanyInvitationService::EMAIL_INVITED]);
    expect(CompanyInvitation::count())->toBe(1);
});

it('creates only one account when the same link is submitted twice', function () {
    $coordinator = invitingCoordinator();
    $token = inviteCompany($coordinator);
    $service = app(CompanyInvitationService::class);

    $service->accept($token, companyForm());
    expect(fn () => $service->accept($token, companyForm()))->toThrow(ValidationException::class);

    expect(User::where('email', 'hr@applus.test')->count())->toBe(1)
        ->and(Employer::count())->toBe(1);
});

it('refuses to accept when the email was registered after the invitation was sent', function () {
    $coordinator = invitingCoordinator();
    $token = inviteCompany($coordinator);
    User::factory()->create(['email' => 'hr@applus.test']);
    auth()->logout();

    $this->post(route('company-invitations.accept', $token), companyForm())
        ->assertSessionHasErrors(['email' => CompanyInvitationService::EMAIL_TAKEN]);
    expect(Employer::count())->toBe(0);
});

it('falls back to an admin when the inviting coordinator is no longer active', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $coordinator = invitingCoordinator();
    $token = inviteCompany($coordinator);
    $coordinator->update(['is_active' => false]);
    auth()->logout();

    $this->post(route('company-invitations.accept', $token), companyForm())->assertRedirect(route('home'));

    expect(Employer::sole()->verified_by_user_id)->toBe($admin->id)
        ->and(AppNotification::where('user_id', $admin->id)->where('type', 'company_invitation')->exists())->toBeTrue()
        ->and(AppNotification::where('user_id', $coordinator->id)->exists())->toBeFalse();
});

it('requires a strong password and the company details', function () {
    $token = inviteCompany(invitingCoordinator());
    auth()->logout();

    $this->post(route('company-invitations.accept', $token), companyForm(['password' => 'weak', 'password_confirmation' => 'weak', 'industry' => '']))
        ->assertSessionHasErrors(['password', 'industry']);
    expect(User::where('email', 'hr@applus.test')->exists())->toBeFalse();
});

it('expires stale invitations with the scheduled command', function () {
    $coordinator = invitingCoordinator();
    inviteCompany($coordinator, ['email' => 'old@company.test']);
    $this->travel(15)->days();
    inviteCompany($coordinator, ['email' => 'new@company.test']);

    $this->artisan('company-invitations:expire')->expectsOutput('Expired 1 company invitation(s).')->assertSuccessful();

    expect(CompanyInvitation::where('email', 'old@company.test')->sole()->status)->toBe(CompanyInvitationStatus::Expired)
        ->and(CompanyInvitation::where('email', 'new@company.test')->sole()->status)->toBe(CompanyInvitationStatus::Sent);
});

it('shows coordinators only their own invitations and lets admins see and filter all of them', function () {
    $mine = invitingCoordinator('mine@scc.test');
    $theirs = invitingCoordinator('theirs@scc.test');
    inviteCompany($mine, ['company_name' => 'Mine Co', 'email' => 'mine@company.test']);
    inviteCompany($theirs, ['company_name' => 'Theirs Co', 'email' => 'theirs@company.test']);

    $this->actingAs($mine)->get(route('coordinator.companies.invitations.index'))
        ->assertSee('Mine Co')->assertDontSee('Theirs Co');

    // Another coordinator cannot resend someone else's invitation.
    $theirsInvitation = CompanyInvitation::where('email', 'theirs@company.test')->sole();
    $this->post(route('coordinator.companies.invitations.resend', $theirsInvitation))->assertForbidden();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.company-invitations.index'))->assertOk()
        ->assertSee('Mine Co')->assertSee('Theirs Co');
    $this->get(route('admin.company-invitations.index', ['coordinator' => $theirs->id]))
        ->assertSee('Theirs Co')->assertDontSee('Mine Co');
    $this->get(route('admin.company-invitations.index', ['status' => 'accepted']))
        ->assertSee('No company invitations match.');
});

it('blocks students, employers, and admins from the coordinator invitation actions', function () {
    $invitation = CompanyInvitation::create([
        'company_name' => 'X', 'email' => 'x@x.test', 'token_hash' => str_repeat('a', 64),
        'status' => CompanyInvitationStatus::Sent, 'expires_at' => now()->addDay(),
    ]);

    foreach ([UserRole::Student, UserRole::Employer, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('coordinator.companies.invitations.index'))->assertForbidden();
        $this->post(route('coordinator.companies.invitations.store'), ['company_name' => 'Y', 'email' => 'y@y.test'])->assertForbidden();
        $this->post(route('coordinator.companies.invitations.resend', $invitation))->assertForbidden();
    }
    foreach ([UserRole::Student, UserRole::Employer, UserRole::Coordinator] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('admin.company-invitations.index'))->assertForbidden();
    }
    expect(CompanyInvitation::count())->toBe(1);
});

it('keeps expiry and submission dates fixed when other columns of the row change', function () {
    // On MariaDB with explicit_defaults_for_timestamp = 0, a NOT NULL timestamp auto-updates on every
    // change, which expired invitations the moment the link was opened. These columns must stay nullable.
    foreach (['company_invitations' => 'expires_at', 'internship_invitations' => 'expires_at', 'evaluations' => 'submitted_at'] as $table => $column) {
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);
        expect($definition['nullable'])->toBeTrue("{$table}.{$column} must be nullable");
    }

    $token = inviteCompany(invitingCoordinator());
    $expiresAt = CompanyInvitation::sole()->expires_at->toDateTimeString();
    auth()->logout();
    $this->travel(5)->minutes();

    $this->get(route('company-invitations.show', $token))->assertOk();
    expect(CompanyInvitation::sole()->expires_at->toDateTimeString())->toBe($expiresAt);
    $this->post(route('company-invitations.accept', $token), companyForm())->assertRedirect(route('home'));
});

it('keeps the form and shows errors when the company details are invalid', function () {
    $token = inviteCompany(invitingCoordinator());
    auth()->logout();

    $this->from(route('company-invitations.show', $token))
        ->post(route('company-invitations.accept', $token), companyForm(['industry' => '']))
        ->assertRedirect(route('company-invitations.show', $token))
        ->assertSessionHasErrors('industry')
        ->assertSessionMissing('company_account_ready');
    expect(CompanyInvitation::sole()->status)->toBe(CompanyInvitationStatus::Sent);
});

it('shows the success banner on the landing page once, with the email for the sign in prefill', function () {
    $token = inviteCompany(invitingCoordinator());
    auth()->logout();
    $this->post(route('company-invitations.accept', $token), companyForm())->assertRedirect(route('home'));

    $this->get(route('home'))->assertOk()
        ->assertSee('data-lf-banner', false)
        ->assertSee('data-email="hr@applus.test"', false);

    // The flash is one-time: a refresh shows no banner.
    $this->get(route('home'))->assertOk()->assertDontSee('data-lf-banner', false);
});
