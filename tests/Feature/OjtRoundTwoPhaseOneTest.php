<?php

use App\Enums\ApplicationStatus;
use App\Enums\CompanyInvitationStatus;
use App\Enums\UserRole;
use App\Mail\CompanyInvitationMail;
use App\Models\AppNotification;
use App\Models\CompanyInvitation;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Session\HashedIpSessionHandler;
use App\Support\IpHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** A placement with its student, company account and coordinator, plus an unrelated company. */
function p1Placement(): array
{
    $institution = Institution::create(['name' => 'Round Two College']);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Applus Labs', 'contact_person' => 'Ian Supervisor']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program' => 'BSIT']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    assignDepartment(Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]), $student);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Developer Trainee', 'description' => 'Build.', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id,
        'internship_id' => $internship->id, 'start_date' => '2026-02-02', 'required_hours' => 500, 'status' => Placement::STATUS_ACTIVE,
    ]);

    return compact('placement', 'studentUser', 'employerUser', 'coordinatorUser', 'student');
}

function p1Notify(User $user, array $data): AppNotification
{
    return app(NotificationService::class)->send($user, 'ojt_update', 'Title', 'Message', $data, email: false);
}

// ---------------------------------------------------------------------------
// 1.1 Clicking a notification opens the page it is about
// ---------------------------------------------------------------------------

it('opens the page a notification is about, on the right tab for each role', function () {
    $f = p1Placement();
    $id = $f['placement']->id;
    $open = fn (User $user, array $data) => test()->actingAs($user)->get(route('notifications.show', p1Notify($user, $data)));

    // A weekly report notice lands on the weekly report tab of that role's own page.
    $report = ['placement_id' => $id, 'section' => 'weekly-journal'];
    $open($f['studentUser'], $report)->assertRedirect(route('student.ojt.index', ['placement' => $id, 'tab' => 'weekly-journal']).'#ojtDetails');
    $open($f['employerUser'], $report)->assertRedirect(route('employer.interns.show', ['placement' => $id, 'tab' => 'weekly-journal']));
    $open($f['coordinatorUser'], $report)->assertRedirect(route('coordinator.placements.show', $id).'#weekly-reports');

    // Time records and the final evaluation (students have no final evaluation tab: Progress shows the result).
    $open($f['studentUser'], ['placement_id' => $id, 'section' => 'time-record'])
        ->assertRedirect(route('student.ojt.index', ['placement' => $id, 'tab' => 'time-record']).'#ojtDetails');
    $open($f['studentUser'], ['placement_id' => $id, 'section' => 'evaluation'])
        ->assertRedirect(route('student.ojt.index', ['placement' => $id, 'tab' => 'progress']).'#ojtDetails');
    $open($f['employerUser'], ['placement_id' => $id, 'section' => 'evaluation'])
        ->assertRedirect(route('employer.interns.show', ['placement' => $id, 'tab' => 'evaluation']));
    $open($f['coordinatorUser'], ['placement_id' => $id, 'section' => 'evaluation'])
        ->assertRedirect(route('coordinator.placements.show', $id).'#evaluations');

    // Deadline reminders carry the kind of document, not a section.
    $open($f['studentUser'], ['placement_id' => $id, 'kind' => 'monthly_evaluation'])
        ->assertRedirect(route('student.ojt.index', ['placement' => $id, 'tab' => 'monthly-evaluation']).'#ojtDetails');

    // No section: the placement's overview.
    $open($f['employerUser'], ['placement_id' => $id])->assertRedirect(route('employer.interns.show', ['placement' => $id, 'tab' => 'progress']));

    // Opening it also marks it read.
    expect(AppNotification::where('user_id', $f['studentUser']->id)->whereNull('read_at')->count())->toBe(0);
});

it('shows the notification itself when its page is gone or the viewer may not open it', function () {
    $f = p1Placement();
    $other = p1Placement();

    // The item was deleted: no error, just the notification.
    $gone = p1Notify($f['studentUser'], ['placement_id' => 999999, 'section' => 'weekly-journal']);
    $this->actingAs($f['studentUser'])->get(route('notifications.show', $gone))->assertOk()->assertSee('Message');

    // A notification that points at another company's placement never leads there.
    $foreign = p1Notify($f['employerUser'], ['placement_id' => $other['placement']->id, 'section' => 'weekly-journal']);
    $this->actingAs($f['employerUser'])->get(route('notifications.show', $foreign))->assertOk();

    // One without any item (an announcement-style notice) stays on the detail page.
    $plain = p1Notify($f['studentUser'], []);
    $this->actingAs($f['studentUser'])->get(route('notifications.show', $plain))->assertOk();

    // Someone else's notification is still forbidden.
    $this->actingAs($f['employerUser'])->get(route('notifications.show', $plain))->assertForbidden();
});

// ---------------------------------------------------------------------------
// 1.2 – 1.4 Company invitations
// ---------------------------------------------------------------------------

function p1Coordinator(): User
{
    $institution = Institution::firstOrCreate(['name' => 'St. Cecilia\'s College']);
    $user = User::factory()->create(['role' => UserRole::Coordinator, 'email' => 'coord@scc.test', 'name' => 'Maria Santos', 'phone' => '09170001111']);
    $coordinator = Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id, 'office_address' => 'IT Building, Room 2']);
    $coordinator->update(['program_id' => \App\Models\Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'required_internship_hours' => 486])->id]);

    return $user;
}

function p1Invite(User $coordinator, array $overrides = []): string
{
    test()->actingAs($coordinator)->post(route('coordinator.companies.invitations.store'), [
        'company_name' => 'Applus Velosi Philippines Inc.', 'address' => 'IT Park, Cebu City',
        'email' => 'hr@applus.test', 'phone' => '09171234567', ...$overrides,
    ])->assertSessionHasNoErrors();

    $url = null;
    Mail::assertSent(CompanyInvitationMail::class, function (CompanyInvitationMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    return basename(parse_url($url, PHP_URL_PATH));
}

function p1CompanyForm(array $overrides = []): array
{
    return [
        'industry' => 'Information Technology', 'contact_person' => 'Ian Olmoguez', 'description' => 'Engineering services.',
        'website' => 'https://applus.test', 'password' => 'Secret#2026', 'password_confirmation' => 'Secret#2026', ...$overrides,
    ];
}

it('locks the details the coordinator entered, on the page and on the server', function () {
    Mail::fake();
    $coordinator = p1Coordinator();
    $token = p1Invite($coordinator);
    auth()->logout();

    $page = $this->get(route('company-invitations.show', $token))->assertOk();
    // Name, address and phone are read-only, shown without a form field of their own.
    $page->assertSee('id="ciCompany" class="form-control" value="Applus Velosi Philippines Inc." readonly', false)
        ->assertSee('id="ciAddress" class="form-control" value="IT Park, Cebu City" readonly', false)
        ->assertSee('id="ciPhone" class="form-control" value="09171234567" readonly', false)
        ->assertDontSee('name="company_name"', false)
        ->assertDontSee('name="address"', false);

    // Editing the page does not help: the server uses the invitation's own details.
    $this->post(route('company-invitations.accept', $token), p1CompanyForm([
        'company_name' => 'Evil Corp', 'address' => 'Somewhere else', 'phone' => '0999', 'email' => 'other@evil.test',
    ]))->assertRedirect(route('home'));

    $employer = Employer::where('company_name', '!=', 'Applus Labs')->sole();
    $user = $employer->user;
    expect($employer->company_name)->toBe('Applus Velosi Philippines Inc.')
        ->and($employer->address)->toBe('IT Park, Cebu City')
        ->and($user->phone)->toBe('09171234567')
        ->and($user->email)->toBe('hr@applus.test')
        ->and(Employer::where('company_name', 'Evil Corp')->exists())->toBeFalse();
});

it('makes the coordinator enter the address and phone, and lets an older invitation without them be completed', function () {
    Mail::fake();
    $coordinator = p1Coordinator();
    $this->actingAs($coordinator)->post(route('coordinator.companies.invitations.store'), ['company_name' => 'No Phone Co', 'email' => 'x@nophone.test'])
        ->assertSessionHasErrors(['address', 'phone']);

    // An invitation sent before this rule has no address or phone: the company adds them.
    $old = CompanyInvitation::create([
        'invited_by_user_id' => $coordinator->id, 'company_name' => 'Old Co', 'email' => 'old@co.test', 'address' => null, 'phone' => null,
        'token_hash' => CompanyInvitation::hashToken('oldtokenxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'status' => CompanyInvitationStatus::Sent, 'expires_at' => now()->addDays(3), 'reference' => 'CI-OLD-1',
    ]);
    auth()->logout();
    $this->get(route('company-invitations.show', 'oldtokenxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'))->assertOk()->assertSee('name="address"', false)->assertSee('name="phone"', false);
    $this->post(route('company-invitations.accept', 'oldtokenxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), p1CompanyForm(['address' => 'Typed Address', 'phone' => '09170000000']))->assertRedirect(route('home'));
    expect(Employer::where('company_name', 'Old Co')->sole()->address)->toBe('Typed Address');
});

it('sends a formal letter before the link, with the reference, expiry and coordinator contact', function () {
    Mail::fake();
    $coordinator = p1Coordinator();
    $token = p1Invite($coordinator);

    Mail::assertSent(CompanyInvitationMail::class, function (CompanyInvitationMail $mail) use ($token) {
        $html = $mail->render();
        $expiry = $mail->invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A');

        // The letter comes first; the button and link follow it.
        expect(strpos($html, 'Dear Applus Velosi Philippines Inc.,'))->toBeInt()->toBeLessThan(strpos($html, 'Accept Invitation and Create Account'))
            ->and($html)->toContain("St. Cecilia's College", 'BS Information Technology (BSIT)', 'Maria Santos', 'coord@scc.test', '09170001111', 'IT Building, Room 2')
            ->and($html)->toContain($mail->invitation->reference, $expiry, 'you may disregard this email');
        $mail->assertSeeInText('Dear Applus Velosi Philippines Inc.,');
        $mail->assertSeeInText($token);

        return $mail->hasSubject('Invitation to partner with St. Cecilia\'s College – Cebu for the OJT Program');
    });
});

it('expires an invitation after 7 days by default', function () {
    Mail::fake();
    p1Invite(p1Coordinator());

    $expires = CompanyInvitation::sole()->expires_at;
    expect($expires->isAfter(now()->addDays(6)->addHours(23)))->toBeTrue()->and($expires->isBefore(now()->addDays(7)->addMinutes(5)))->toBeTrue();
});

it('lets only an admin change the expiry of an unused invitation, within 30 days', function () {
    Mail::fake();
    $coordinator = p1Coordinator();
    $token = p1Invite($coordinator);
    $invitation = CompanyInvitation::sole();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $change = fn (string $when) => test()->put(route('admin.company-invitations.expiry', $invitation), ['expires_at' => $when]);
    $manila = fn (int $days) => now('Asia/Manila')->addDays($days)->format('Y-m-d\TH:i');

    // The coordinator who sent it cannot.
    $this->actingAs($coordinator)->put(route('admin.company-invitations.expiry', $invitation), ['expires_at' => $manila(10)])->assertForbidden();

    $this->actingAs($admin);
    $page = $this->get(route('admin.company-invitations.index'))->assertOk();
    $page->assertSee('Change expiry');

    $change($manila(10))->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($invitation->fresh()->expires_at->isBetween(now()->addDays(9)->addHours(23), now()->addDays(10)->addHours(1)))->toBeTrue();
    expect(SystemLog::where('action', 'company_invitation_expiry_changed')->where('user_id', $admin->id)->exists())->toBeTrue();

    // In the past, or more than 30 days away: refused, and nothing changes.
    $before = $invitation->fresh()->expires_at;
    $change($manila(-1))->assertSessionHasErrors('expires_at');
    $change($manila(31))->assertSessionHasErrors('expires_at');
    $change('not a date')->assertSessionHasErrors('expires_at');
    expect($invitation->fresh()->expires_at->equalTo($before))->toBeTrue();

    // An expired link that was never used works again, with the same link from the email.
    $invitation->update(['expires_at' => now()->subDay(), 'status' => CompanyInvitationStatus::Expired]);
    $change($manila(5))->assertSessionHasNoErrors();
    expect($invitation->fresh()->status)->toBe(CompanyInvitationStatus::Sent);
    auth()->logout();
    $this->get(route('company-invitations.show', $token))->assertOk();

    // After the company registered, the expiry is final.
    $this->post(route('company-invitations.accept', $token), p1CompanyForm())->assertRedirect(route('home'));
    $this->actingAs($admin)->put(route('admin.company-invitations.expiry', $invitation), ['expires_at' => $manila(5)])->assertForbidden();
});

// ---------------------------------------------------------------------------
// 1.5 Printed forms
// ---------------------------------------------------------------------------

it('prints the certifying supervisor from the OJT, not whoever clicked', function () {
    $f = p1Placement();
    $f['placement']->update(['supervisor_name' => 'Maria Assigned Supervisor']);
    $data = (new \App\Services\OjtDocumentData)->values($f['placement']->fresh());

    expect($data['supervisor_name'])->toBe('Maria Assigned Supervisor');
});

// ---------------------------------------------------------------------------
// 1.6 IP addresses are stored hashed
// ---------------------------------------------------------------------------

it('hashes IP addresses with a keyed hash, the same address always giving the same code', function () {
    $key = config('privacy.ip_hash_key');
    expect($key)->not->toBe(config('app.key'))->and(IpHash::make('203.0.113.9'))->toBe(hash_hmac('sha256', '203.0.113.9', $key))
        ->and(IpHash::make('203.0.113.9'))->toBe(IpHash::make('203.0.113.9'))
        ->and(IpHash::make('203.0.113.10'))->not->toBe(IpHash::make('203.0.113.9'))
        ->and(IpHash::make(null))->toBeNull()
        ->and(IpHash::label(IpHash::make('203.0.113.9')))->toBe('ip·'.substr(IpHash::make('203.0.113.9'), 0, 8))
        ->and(IpHash::label(null))->toBe('—');

    // No key set: nothing is recorded, and a plain address is never shown.
    config(['privacy.ip_hash_key' => '']);
    expect(IpHash::make('203.0.113.9'))->toBeNull()->and(IpHash::limiterKey('203.0.113.9'))->toBe('203.0.113.9')
        ->and(IpHash::label('203.0.113.9'))->not->toContain('203.0.113.9');
});

it('stores only the hash in the audit log, and lets an admin search by address or short code', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->post(route('login'), ['login' => 'nobody@example.test', 'password' => 'wrong'])
        ->assertSessionHasErrors();

    $stored = SystemLog::where('action', 'login_failed')->latest('id')->value('ip_address');
    expect($stored)->toBe(IpHash::make('203.0.113.9'))->and(strlen($stored))->toBe(64);
    expect(DB::table('system_logs')->where('ip_address', '203.0.113.9')->exists())->toBeFalse();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    $code = 'ip·'.substr($stored, 0, 8);
    // The page shows the short code, never the address.
    $this->get(route('admin.logs'))->assertOk()->assertSee($code)->assertDontSee('203.0.113.9');
    // Searching with the real address, or with the short code, finds the row; another address does not.
    $this->get(route('admin.logs', ['q' => '203.0.113.9', 'action' => 'login_failed']))->assertSee($code);
    $this->get(route('admin.logs', ['q' => $code, 'action' => 'login_failed']))->assertSee($code);
    $this->get(route('admin.logs', ['q' => '203.0.113.77', 'action' => 'login_failed']))->assertDontSee($code);
});

it('stores a hash instead of the address in the session table', function () {
    $this->app->instance('request', Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.51.100.7']));
    $handler = new HashedIpSessionHandler(DB::connection(), 'sessions', 120, $this->app);
    $handler->write('session-one', 'payload');

    expect(DB::table('sessions')->where('id', 'session-one')->value('ip_address'))->toBe(IpHash::make('198.51.100.7'))
        ->and(DB::table('sessions')->where('ip_address', '198.51.100.7')->exists())->toBeFalse();
});

it('hashes the addresses already stored, once', function () {
    DB::table('system_logs')->insert(['action' => 'old_entry', 'ip_address' => '192.0.2.44', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('sessions')->insert(['id' => 'old-session', 'ip_address' => '192.0.2.45', 'payload' => 'x', 'last_activity' => time()]);

    $migration = require database_path('migrations/2026_11_21_000001_hash_stored_ip_addresses.php');
    $migration->up();
    $migration->up(); // running it again leaves the hashes alone

    expect(DB::table('system_logs')->where('action', 'old_entry')->value('ip_address'))->toBe(IpHash::make('192.0.2.44'))
        ->and(DB::table('sessions')->where('id', 'old-session')->value('ip_address'))->toBe(IpHash::make('192.0.2.45'));

    config(['privacy.ip_hash_key' => '']);
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'IP_HASH_KEY is not set');
});
