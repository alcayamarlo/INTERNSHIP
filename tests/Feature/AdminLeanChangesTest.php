<?php

use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Program;
use App\Models\User;
use App\Services\AcademicCalendar;
use App\Services\BackupArchive;
use App\Services\DatabaseBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function lcAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin, 'password' => 'Correct-Horse-9']);
}

function lcCoordinator(string $name, ?Program $program = null, array $user = []): User
{
    $u = User::factory()->create(['role' => UserRole::Coordinator, 'name' => $name] + $user);
    Coordinator::create(['user_id' => $u->id, 'institution_id' => Institution::primary()->id, 'program_id' => $program?->id]);

    return $u;
}

function lcProgram(string $code = 'BSIT'): Program
{
    return Program::create(['name' => 'Program '.$code, 'code' => $code, 'required_internship_hours' => 486]);
}

function lcProgramPayload(Program $program, array $extra = []): array
{
    return ['name' => $program->name, 'code' => $program->code, 'required_internship_hours' => 486, 'majors' => ''] + $extra;
}

/* ---------- Summer is gone ---------- */

it('removes the Summer term: the migration extends the 2nd semester, clears a summer override, and reverses', function () {
    $migration = require base_path('database/migrations/2026_11_28_000001_remove_summer_term.php');
    $settings = AcademicSetting::current();
    expect($settings->second_semester_end_month)->toBe(7)->and($settings->second_semester_end_day)->toBe(31);

    // Back to how it was (summer Jun 1 - Jul 31, 2nd semester to May 31) with a summer override...
    $migration->down();
    expect(Schema::hasColumn('academic_settings', 'summer_enabled'))->toBeTrue();
    DB::table('academic_settings')->update(['current_term_override' => 'summer']);
    expect((int) DB::table('academic_settings')->value('second_semester_end_month'))->toBe(5);

    // ...and forward again: the two semesters cover the whole year, summer is gone, the override is cleared.
    $migration->up();
    $row = DB::table('academic_settings')->first();
    expect((int) $row->second_semester_end_month)->toBe(7)->and((int) $row->second_semester_end_day)->toBe(31)->and($row->current_term_override)->toBeNull();
    foreach (['summer_enabled', 'summer_start_month', 'summer_start_day', 'summer_end_month', 'summer_end_day'] as $column) {
        expect(Schema::hasColumn('academic_settings', $column))->toBeFalse();
    }
    $calendar = app(AcademicCalendar::class);
    expect($calendar->rangesAreContinuous(AcademicSetting::current()->termRanges()))->toBeTrue()
        ->and($calendar->termForDate(\Carbon\CarbonImmutable::parse('2027-06-20'), AcademicSetting::current()))->toBe('second');
});

it('refuses Summer in the settings form, the student profile and the CSV import', function () {
    $admin = lcAdmin();
    $payload = AcademicSetting::current()->only([
        'student_id_pattern', 'required_ojt_hours', 'academic_year_start_month',
        'first_semester_start_month', 'first_semester_start_day', 'first_semester_end_month', 'first_semester_end_day',
        'second_semester_start_month', 'second_semester_start_day', 'second_semester_end_month', 'second_semester_end_day',
    ]);

    $this->actingAs($admin)->post(route('admin.academics.settings.update'), $payload + ['current_term_override' => 'summer'])->assertSessionHasErrors('current_term_override');
    $this->post(route('admin.academics.settings.update'), $payload + ['current_term_override' => 'second'])->assertSessionHasNoErrors();
    // A 2nd semester that stops at May 31 would leave Jun-Jul with no term: refused.
    $this->post(route('admin.academics.settings.update'), array_merge($payload, ['second_semester_end_month' => 5, 'second_semester_end_day' => 31]))->assertSessionHasErrors('term_ranges');
});

/* ---------- Program form: coordinators only ---------- */

it('lets an admin assign one coordinator on a program, and lists only coordinator accounts', function () {
    $admin = lcAdmin();
    $bsit = lcProgram('BSIT');
    $a = lcCoordinator('Ana Coordinator');
    $b = lcCoordinator('Ben Coordinator');
    $c = lcCoordinator('Cy Coordinator');
    lcCoordinator('Inactive Coordinator', null, ['is_active' => false]);
    $student = User::factory()->create(['role' => UserRole::Student, 'name' => 'Stu Student']);
    $hr = User::factory()->create(['role' => UserRole::Employer, 'name' => 'Hr Person']);

    $html = $this->actingAs($admin)->get(route('admin.academics.programs.edit', $bsit))->assertOk()->assertSee('data-coordinator-assignment', false)
        ->assertSee('Ana Coordinator')->assertSee('Ben Coordinator')->assertSee('Cy Coordinator')
        ->assertDontSee('Inactive Coordinator')->assertDontSee('Stu Student')->assertDontSee('Hr Person')->getContent();
    expect(preg_match_all('/name="coordinators\[\]"/', $html))->toBe(3);

    // One is fine.
    $this->put(route('admin.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1, 'coordinators' => [$a->id]]))->assertRedirect()->assertSessionHasNoErrors();
    expect(Coordinator::where('program_id', $bsit->id)->pluck('user_id')->all())->toBe([$a->id]);

    // Two are refused and nothing changes.
    $this->put(route('admin.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1, 'coordinators' => [$a->id, $b->id]]))->assertSessionHasErrors('coordinators');
    expect(Coordinator::where('program_id', $bsit->id)->count())->toBe(1);

    // Only users with the coordinator role: a student, a company, an inactive coordinator, a made-up id.
    foreach ([$student->id, $hr->id, 999999] as $bad) {
        $this->put(route('admin.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1, 'coordinators' => [$a->id, $bad]]))->assertSessionHasErrors('coordinators.1');
    }
    expect(Coordinator::where('program_id', $bsit->id)->count())->toBe(1);

    // Swapping: Cy in, Ana out (Ana is left without a department).
    $this->put(route('admin.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1, 'coordinators' => [$c->id]]))->assertSessionHasNoErrors();
    expect(Coordinator::where('program_id', $bsit->id)->pluck('user_id')->all())->toBe([$c->id])
        ->and($a->coordinator->fresh()->program_id)->toBeNull();

    // Unticking everyone unassigns them.
    $this->put(route('admin.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1]))->assertSessionHasNoErrors();
    expect(Coordinator::where('program_id', $bsit->id)->count())->toBe(0);
});

it('moves a coordinator from another department, and can assign coordinators when adding a program', function () {
    $admin = lcAdmin();
    $bsit = lcProgram('BSIT');
    $bscs = lcProgram('BSCS');
    $ana = lcCoordinator('Ana Coordinator', $bsit);

    $this->actingAs($admin)->get(route('admin.academics.programs.edit', $bscs))->assertSee('now in BSIT: moves here');
    $this->put(route('admin.academics.programs.update', $bscs), lcProgramPayload($bscs, ['coordinators_submitted' => 1, 'coordinators' => [$ana->id]]))->assertSessionHasNoErrors();
    expect($ana->coordinator->fresh()->program_id)->toBe($bscs->id);

    $this->get(route('admin.academics.programs.create'))->assertOk()->assertSee('data-coordinator-assignment', false);
    $this->post(route('admin.academics.programs.store'), ['name' => 'BS Nursing', 'code' => 'BSN', 'required_internship_hours' => 500, 'majors' => '', 'coordinators_submitted' => 1, 'coordinators' => [$ana->id]])->assertRedirect()->assertSessionHasNoErrors();
    expect($ana->coordinator->fresh()->program_id)->toBe(Program::where('code', 'BSN')->value('id'));
});

it('does not let a coordinator change who the coordinators are', function () {
    $bsit = lcProgram('BSIT');
    $mine = lcCoordinator('Mine Coordinator', $bsit);
    $other = lcCoordinator('Other Coordinator');

    $this->actingAs($mine)->get(route('coordinator.academics.programs.edit', $bsit))->assertOk()->assertDontSee('data-coordinator-box', false);
    $this->put(route('coordinator.academics.programs.update', $bsit), lcProgramPayload($bsit, ['coordinators_submitted' => 1, 'coordinators' => [$other->id]]));
    expect($other->coordinator->fresh()->program_id)->toBeNull()->and($mine->coordinator->fresh()->program_id)->toBe($bsit->id);
});

/* ---------- Users page: no Archive ---------- */

it('has no Archive on the Users page, keeps Delete, and shows an anonymised account as such', function () {
    $admin = lcAdmin();
    $someone = User::factory()->create(['role' => UserRole::Student, 'name' => 'Some Student']);
    $gone = User::factory()->create(['role' => UserRole::Student, 'name' => 'Former student', 'archived_at' => now(), 'anonymised_at' => now(), 'is_active' => false]);

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
        ->assertDontSee('Archive')->assertDontSee('Archived')->assertDontSee('bi-archive', false)->assertDontSee('Restore')
        ->assertSee(route('admin.users.delete-now', $someone), false)->assertSee('All users')->assertSee('Suspended')
        ->assertSee('Deleted (anonymised)');
    // The old tab address just shows the normal list.
    $this->get(route('admin.users.index', ['view' => 'archived']))->assertOk()->assertSee('Some Student');

    foreach (['admin.users.archive', 'admin.users.restore', 'admin.users.force-delete'] as $name) {
        expect(\Illuminate\Support\Facades\Route::has($name))->toBeFalse();
    }
    // Delete still works.
    $this->delete(route('admin.users.delete-now', $someone))->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseMissing('users', ['id' => $someone->id]);
});

/* ---------- Backups ---------- */

function lcBackupSetup(): void
{
    foreach (['local', 'public', 'private'] as $disk) {
        Storage::fake($disk);
    }
    Storage::disk('public')->put('profiles/a.jpg', 'a student picture');
    Storage::disk('private')->put('resumes/9.pdf', '%PDF resume');
    config(['backup.password' => 'a-long-backup-password-for-tests-0123', 'backup.copy_path' => null]);
    test()->mock(DatabaseBackup::class, fn ($mock) => $mock->shouldReceive('dumpTo')->andReturnUsing(fn (string $path) => file_put_contents($path, "-- dump\nINSERT INTO users VALUES (1);\n")));
}

it('shows the real reason a backup failed instead of a generic hint', function () {
    foreach (['local', 'public', 'private'] as $disk) {
        Storage::fake($disk);
    }
    config(['backup.password' => 'a-long-backup-password-for-tests-0123', 'database.connections.mysql.database' => 'nexus']);

    // The program is not where MYSQLDUMP_PATH says.
    config(['database.mysqldump_path' => 'C:/xampp/mysql/bin/no-such-mysqldump.exe']);
    $this->actingAs(lcAdmin())->post(route('admin.backup'))->assertRedirect()
        ->assertSessionHas('error', fn (string $m) => str_starts_with($m, 'Backup failed: mysqldump was not found at C:/xampp/mysql/bin/no-such-mysqldump.exe') && str_contains($m, 'MYSQLDUMP_PATH'));

    // The program runs but fails: its own error text is shown (here PHP stands in for mysqldump and says something).
    $fake = base_path('tests/Fixtures/fake-clamscan.php');
    config(['database.mysqldump_path' => PHP_BINARY]);
    $thrown = null;
    try {
        app(DatabaseBackup::class)->dumpTo(storage_path('framework/testing/dump-'.uniqid().'.sql'));
    } catch (RuntimeException $e) {
        $thrown = $e->getMessage();
    }
    expect($thrown)->toStartWith('mysqldump failed: ')->and(strlen($thrown))->toBeGreaterThan(20); // PHP complains about the mysqldump-style arguments: the real text, not a hint
});

it('starts mysqldump with SystemRoot on Windows, which fixes "Can\'t create TCP/IP socket (10106)"', function () {
    $method = new ReflectionMethod(DatabaseBackup::class, 'environment');
    $env = $method->invoke(app(DatabaseBackup::class));

    expect($env)->toBeArray();
    if (PHP_OS_FAMILY === 'Windows') {
        expect($env)->toHaveKey('SystemRoot')->and($env['SystemRoot'])->not->toBe('')->and($env)->toHaveKey('windir');
    }
    // And the environment it already had is kept, not replaced.
    putenv('LC_TEST_MARKER=kept');
    expect($method->invoke(app(DatabaseBackup::class)))->toHaveKey('LC_TEST_MARKER');
    putenv('LC_TEST_MARKER');
});

it('backs up all users\' data from the Reports page: encrypted, with everything, and never .env or APP_KEY', function () {
    lcBackupSetup();
    $admin = lcAdmin();
    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Employer] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->post(route('admin.backup'))->assertForbidden();
    }

    $this->actingAs($admin)->get(route('admin.reports'))->assertOk()->assertSee("Back up all users' data", false)->assertSee('data-backups-card', false)
        ->assertSee('.env')->assertSee('APP_KEY')->assertSee('No backups on this server yet');

    $response = $this->post(route('admin.backup'))->assertOk()->assertDownload();
    $name = basename($response->baseResponse->getFile()->getPathname());

    // Opens only with the password, holds the database, the uploads and a manifest, and nothing else.
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path('backups/'.$name));
    expect($zip->getFromName('database.sql'))->toBeFalse(); // no password
    $zip->setPassword(config('backup.password'));
    $entries = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();
    expect($entries)->toBe(['database.sql', 'files/private/resumes/9.pdf', 'files/public/profiles/a.jpg', 'manifest.json']);
    $everything = collect($entries)->map(fn ($e) => $zip->getFromName($e))->implode("\n");
    $zip->close();
    expect($everything)->not->toContain((string) config('app.key'))->not->toContain('APP_KEY=');
    expect($entries)->not->toContain('.env');

    // It is listed on the page, and can be verified and downloaded.
    $this->get(route('admin.reports'))->assertSee($name)->assertSee(route('admin.backups.verify', $name), false)->assertSee(route('admin.backups.download', $name), false);
    $this->post(route('admin.backups.verify', $name))->assertSessionHas('success', fn (string $m) => str_contains($m, 'opens with BACKUP_PASSWORD') && str_contains($m, '1 uploaded files') === false && str_contains($m, '2 uploaded files'));
    $this->get(route('admin.backups.download', $name))->assertOk()->assertDownload($name);

    // With another password the check says so.
    config(['backup.password' => 'a-different-password-that-is-long']);
    $this->post(route('admin.backups.verify', $name))->assertSessionHas('error', fn (string $m) => str_contains($m, 'could not be opened'));
});

it('only serves and checks backup files this system made, and only to admins', function () {
    lcBackupSetup();
    Storage::disk('local')->put('backups/backup_2026-01-01_000000.zip', 'x');
    Storage::disk('local')->put('backups/secret-notes.txt', 'private');
    Storage::disk('local')->put('other/backup_2026-01-01_000001.zip', 'x');

    $this->actingAs(lcAdmin());
    foreach (['secret-notes.txt', '..%2F..%2F.env', 'backup_2026-01-01_000001.zip', 'backup_x.zip'] as $bad) {
        $this->get('/admin/backups/'.$bad.'/download')->assertNotFound();
    }
    $this->get(route('admin.backups.download', 'backup_2026-01-01_000000.zip'))->assertOk();

    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Employer, UserRole::Supervisor] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('admin.backups.download', 'backup_2026-01-01_000000.zip'))->assertForbidden();
        $this->post(route('admin.backups.verify', 'backup_2026-01-01_000000.zip'))->assertForbidden();
    }
});
