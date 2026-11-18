<?php

use App\Enums\UserRole;
use App\Mail\SupervisorAccountMail;
use App\Models\AppNotification;
use App\Models\Supervisor;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

function smCreate(array $company, array $overrides = [])
{
    return test()->actingAs($company['hrUser'])->post(route('employer.supervisors.store'), [
        'name' => 'Sam Supervisor', 'email' => 'sam@applus.test', 'position' => 'Senior Engineer', 'phone' => '09171234567', ...$overrides,
    ]);
}

/** The setup link from the email that was sent. */
function smSetupUrl(): string
{
    $url = null;
    Mail::assertSent(SupervisorAccountMail::class, function (SupervisorAccountMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    return $url;
}

it('lets the HR account create a supervisor, who gets an email to set their own password', function () {
    $company = svCompany();
    smCreate($company)->assertRedirect(route('employer.supervisors.index'))->assertSessionHas('success');

    $user = User::where('email', 'sam@applus.test')->sole();
    $supervisor = Supervisor::where('user_id', $user->id)->sole();
    expect($user->role)->toBe(UserRole::Supervisor)->and($user->email_verified_at)->toBeNull()->and($user->phone)->toBe('09171234567')
        ->and($supervisor->employer_id)->toBe($company['employer']->id)->and($supervisor->position)->toBe('Senior Engineer')
        ->and($supervisor->created_by_user_id)->toBe($company['hrUser']->id)
        ->and(SystemLog::where('action', 'supervisor_created')->exists())->toBeTrue();

    Mail::assertSent(SupervisorAccountMail::class, function (SupervisorAccountMail $mail) {
        $html = $mail->render();

        return $mail->hasTo('sam@applus.test')
            && str_contains($html, 'Dear Sam Supervisor,') && str_contains($html, 'Applus Labs') && str_contains($html, 'Senior Engineer')
            && str_contains($html, '7 days') && str_contains($mail->url, '/supervisor-setup/');
    });

    // Nobody knows the starting password: signing in is impossible until the link is used.
    expect(Hash::check('password', $user->password))->toBeFalse();
    $this->post(route('logout'));
    $this->post(route('login'), ['login' => 'sam@applus.test', 'password' => 'password'])->assertSessionHasErrors();
    $this->assertGuest();
});

it('refuses a duplicate email, a missing job title, and everyone but the HR account', function () {
    $company = svCompany();
    smCreate($company)->assertSessionHasNoErrors();
    smCreate($company, ['name' => 'Other Person'])->assertSessionHasErrors(['email' => 'That email already has an account.']);
    smCreate($company, ['email' => 'kim@applus.test', 'position' => ''])->assertSessionHasErrors('position');
    expect(Supervisor::count())->toBe(1);

    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Supervisor] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('employer.supervisors.index'))->assertForbidden();
    }
});

it('lets the supervisor set a password once from the link, then sign in', function () {
    $company = svCompany();
    smCreate($company);
    $url = smSetupUrl();
    $this->post(route('logout'));

    $this->get($url)->assertOk()->assertSee('Set your password')->assertSee('sam@applus.test')->assertSee('Sam Supervisor');
    // Every password rule is listed, so the supervisor sees what is still missing while typing.
    $this->get($url)->assertSee('data-pw-rules', false)->assertSee('8 to 64 characters')->assertSee('An uppercase letter (A-Z)')
        ->assertSee('A lowercase letter (a-z)')->assertSee('A number (0-9)')->assertSee('A special character (! @ # $ %)')->assertSee('Both passwords match')
        ->assertSee('js/sb-password-rules.js', false);

    // A weak password or a mismatch changes nothing.
    $this->post(route('supervisor.setup.store', basename(parse_url($url, PHP_URL_PATH))), ['email' => 'sam@applus.test', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
    expect(User::where('email', 'sam@applus.test')->value('email_verified_at'))->toBeNull();

    $token = basename(parse_url($url, PHP_URL_PATH));
    $this->post(route('supervisor.setup.store', $token), ['email' => 'sam@applus.test', 'password' => 'Secret#2026', 'password_confirmation' => 'Secret#2026'])
        ->assertRedirect(route('home'))->assertSessionHas('signin_email', 'sam@applus.test');
    $user = User::where('email', 'sam@applus.test')->sole();
    expect($user->email_verified_at)->not->toBeNull()->and(Hash::check('Secret#2026', $user->password))->toBeTrue();

    // The link works once.
    $this->get($url)->assertStatus(410)->assertSee('This link is not valid');
    $this->post(route('supervisor.setup.store', $token), ['email' => 'sam@applus.test', 'password' => 'Another#2026', 'password_confirmation' => 'Another#2026'])
        ->assertRedirect(route('supervisor.setup', ['token' => $token, 'email' => 'sam@applus.test']));
    expect(Hash::check('Secret#2026', $user->fresh()->password))->toBeTrue();
});

it('rejects a made-up or someone else\'s setup link', function () {
    $company = svCompany();
    smCreate($company);
    $this->post(route('logout'));
    $token = basename(parse_url(smSetupUrl(), PHP_URL_PATH));

    $this->get(route('supervisor.setup', ['token' => str_repeat('a', 64), 'email' => 'sam@applus.test']))->assertStatus(410);
    // A valid token with another account's email does not work either, and the HR account cannot be reached this way.
    $this->get(route('supervisor.setup', ['token' => $token, 'email' => $company['hrUser']->email]))->assertStatus(410);
    $this->post(route('supervisor.setup.store', $token), ['email' => $company['hrUser']->email, 'password' => 'Secret#2026', 'password_confirmation' => 'Secret#2026']);
    expect(Hash::check('Secret#2026', $company['hrUser']->fresh()->password))->toBeFalse();
});

it('resends a fresh link only while setup is pending, and the old link stops working', function () {
    $company = svCompany();
    smCreate($company);
    $supervisor = Supervisor::sole();
    $first = smSetupUrl();
    Mail::fake();

    $this->post(route('employer.supervisors.resend', $supervisor))->assertSessionHas('success');
    $second = smSetupUrl();
    expect($second)->not->toBe($first);
    $this->post(route('logout'));
    $this->get($first)->assertStatus(410);
    $this->get($second)->assertOk();

    $supervisor->user->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs($company['hrUser'])->post(route('employer.supervisors.resend', $supervisor))->assertSessionHas('error', 'This supervisor already set a password.');
});

it('edits a supervisor, keeping the printed name on their interns in step, and only for the own company', function () {
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $sam = svSupervisor($a['employer']);
    $placement = svPlacement($a['employer'], $sam);
    $placement->update(['supervisor_name' => 'Sam Supervisor']);
    $supervisor = $sam->supervisor;

    $this->actingAs($a['hrUser'])->put(route('employer.supervisors.update', $supervisor), ['name' => 'Samuel Supervisor', 'position' => 'Engineering Manager', 'phone' => '09180000000'])->assertSessionHas('success');
    expect($sam->fresh()->name)->toBe('Samuel Supervisor')->and($supervisor->fresh()->position)->toBe('Engineering Manager')
        ->and($placement->fresh()->supervisor_name)->toBe('Samuel Supervisor');

    $this->actingAs($b['hrUser'])->put(route('employer.supervisors.update', $supervisor), ['name' => 'Hacked', 'position' => 'x'])->assertNotFound();
    $this->patch(route('employer.supervisors.status', $supervisor))->assertNotFound();
    expect($sam->fresh()->name)->toBe('Samuel Supervisor');
});

it('deactivates a supervisor, handing their interns back to HR, and reactivates them', function () {
    $company = svCompany();
    $sam = svSupervisor($company['employer']);
    $placement = svPlacement($company['employer'], $sam);
    $this->actingAs($company['hrUser']);

    $this->patch(route('employer.supervisors.status', $sam->supervisor))->assertSessionHas('success');
    expect($sam->fresh()->is_active)->toBeFalse()->and($placement->fresh()->supervisingUser()->is($company['hrUser']))->toBeTrue();
    // The deactivated account cannot sign in.
    $sam->forceFill(['password' => 'Secret#2026', 'email_verified_at' => now()])->save();
    $this->post(route('logout'));
    $this->post(route('login'), ['login' => $sam->email, 'password' => 'Secret#2026'])->assertSessionHasErrors();
    $this->assertGuest();

    $this->actingAs($company['hrUser'])->patch(route('employer.supervisors.status', $sam->supervisor));
    expect($sam->fresh()->is_active)->toBeTrue()->and($placement->fresh()->supervisingUser()->is($sam))->toBeTrue();
});

it('assigns one supervisor to an intern, clears it, and tells the supervisor', function () {
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $sam = svSupervisor($a['employer'], 'Sam');
    $outsider = svSupervisor($b['employer'], 'Outsider');
    $placement = svPlacement($a['employer']);
    $this->actingAs($a['hrUser']);
    $assign = fn (?int $id, $target = null) => $this->put(route('employer.interns.supervisor', $target ?? $placement), ['supervisor_user_id' => $id]);

    $assign($sam->id)->assertSessionHas('success', 'Supervisor updated for this intern.');
    $placement = $placement->fresh();
    expect($placement->supervisor_user_id)->toBe($sam->id)->and($placement->supervisor_name)->toBe('Sam')->and($placement->activeSupervisor()->is($sam))->toBeTrue();
    $note = AppNotification::where('user_id', $sam->id)->where('title', 'New intern assigned')->sole();
    expect($note->data['placement_id'])->toBe($placement->id);
    $assign($sam->id)->assertSessionHas('success', 'Nothing changed.');

    // Another company's supervisor, another company's intern, a deactivated supervisor: all refused.
    $assign($outsider->id)->assertSessionHasErrors('supervisor_user_id');
    $assign($sam->id, svPlacement($b['employer']))->assertForbidden();
    $kim = svSupervisor($a['employer'], 'Kim');
    $kim->update(['is_active' => false]);
    $assign($kim->id)->assertSessionHasErrors('supervisor_user_id');
    expect($placement->fresh()->supervisor_user_id)->toBe($sam->id);

    // Clearing hands the intern back to HR and removes the printed name.
    $assign(null)->assertSessionHas('success');
    $placement = $placement->fresh();
    expect($placement->supervisor_user_id)->toBeNull()->and($placement->supervisor_name)->toBeNull()->and($placement->supervisingUser()->is($a['hrUser']))->toBeTrue();
});

it('assigns one supervisor to several interns at once, skipping other companies\' interns', function () {
    $a = svCompany('Company A');
    $b = svCompany('Company B');
    $sam = svSupervisor($a['employer'], 'Sam');
    $one = svPlacement($a['employer']);
    $two = svPlacement($a['employer']);
    $already = svPlacement($a['employer'], $sam);
    $foreign = svPlacement($b['employer']);
    $this->actingAs($a['hrUser']);

    $this->post(route('employer.interns.supervisor.bulk'), ['placements' => [$one->id, $two->id, $already->id, $foreign->id], 'supervisor_user_id' => $sam->id])
        ->assertSessionHas('success', 'Supervisor assigned to 2 intern(s).');
    expect($one->fresh()->supervisor_user_id)->toBe($sam->id)->and($two->fresh()->supervisor_user_id)->toBe($sam->id)->and($foreign->fresh()->supervisor_user_id)->toBeNull();
    expect(AppNotification::where('user_id', $sam->id)->where('title', 'New interns assigned')->sole()->message)->toBe('2 interns were assigned to you.');

    $this->post(route('employer.interns.supervisor.bulk'), ['supervisor_user_id' => $sam->id])->assertSessionHasErrors('placements');
    $this->post(route('employer.interns.supervisor.bulk'), ['placements' => [$one->id]])->assertSessionHasErrors('supervisor_user_id');
});

it('shows a "No supervisor assigned" badge on the Interns page, and filters by supervisor', function () {
    $company = svCompany();
    $sam = svSupervisor($company['employer'], 'Sam Hasinterns');
    $kim = svSupervisor($company['employer'], 'Kim Deactivated');
    $assigned = svPlacement($company['employer'], $sam);
    $unassigned = svPlacement($company['employer']);
    $orphaned = svPlacement($company['employer'], $kim);
    $kim->update(['is_active' => false]); // HR covers this intern again
    $this->actingAs($company['hrUser']);

    $page = $this->get(route('employer.interns.index'))->assertOk();
    $page->assertSee('No supervisor assigned')->assertSee('Supervisor deactivated')
        ->assertSee('No supervisor assigned (2)')   // the unassigned one and the one whose supervisor was deactivated
        ->assertSee('2 interns have no supervisor assigned');

    $none = $this->get(route('employer.interns.index', ['supervisor' => 'none']))->assertOk()->getContent();
    expect(substr_count($none, 'name="placements[]"'))->toBe(2)->and($none)->toContain('value="'.$unassigned->id.'"')->and($none)->toContain('value="'.$orphaned->id.'"');

    $mine = $this->get(route('employer.interns.index', ['supervisor' => $sam->id]))->getContent();
    expect(substr_count($mine, 'name="placements[]"'))->toBe(1)->and($mine)->toContain('value="'.$assigned->id.'"');

    // The Supervisors page lists them with their intern counts and statuses.
    svSupervisor($company['employer'], 'Pat Pending')->forceFill(['email_verified_at' => null])->save();
    $this->get(route('employer.supervisors.index'))->assertOk()->assertSee('Sam Hasinterns')->assertSee('1 intern')->assertSee('Deactivated')->assertSee('Setup pending');
});
