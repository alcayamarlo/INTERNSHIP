<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\AccountDeletionRequest;
use App\Models\Appraisal;
use App\Models\AppNotification;
use App\Models\Certificate;
use App\Models\CharacterReference;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Models\Message;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\AccountAnonymizer;
use App\Services\BackupArchive;
use App\Services\DatabaseBackup;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

const AD_PASSWORD = 'Correct-Horse-9';

/** Fake disks, a backup password and a fake database dump. $onDump runs at the moment the backup dumps the database. */
function adEnvironment(?Closure $onDump = null, bool $failBackup = false): void
{
    foreach (['public', 'private', 'local'] as $disk) {
        Storage::fake($disk);
    }
    config(['backup.password' => 'a-long-backup-password-for-tests-0123', 'backup.copy_path' => null]);
    test()->mock(DatabaseBackup::class, fn ($mock) => $mock->shouldReceive('dumpTo')->andReturnUsing(function (string $path) use ($onDump, $failBackup) {
        if ($failBackup) {
            throw new RuntimeException('mysqldump failed: connection refused');
        }
        $onDump && $onDump();
        file_put_contents($path, "-- dump\nINSERT INTO users VALUES (1);\n");
    }));
}

function adAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin, 'password' => AD_PASSWORD]);
}

/**
 * A student with an OJT and a full profile: personal fields, files on every disk, records of every kind, messages,
 * notifications and a session. Returns the user, the student, the placement and every file path.
 */
function adStudent(string $name = 'Hazel Villaflor', string $email = 'hazel@school.test', string $schoolId = 'SCC-23-00024040', string $phone = '09240173281', string $emergencyPhone = '09244473927', string $place = 'Tunghaan, Minglanilla'): array
{
    [$first, $last] = explode(' ', $name);
    $company = svCompany('Acme '.crc32($schoolId));
    $placement = svPlacement($company['employer']);
    $student = $placement->student;
    $user = $student->user;
    $user->forceFill(['name' => $name, 'email' => $email, 'phone' => $phone, 'avatar' => "avatars/{$schoolId}.png", 'password' => AD_PASSWORD, 'email_verified_at' => now(), 'must_change_password' => false, 'last_login_at' => now()])->saveQuietly(); // quietly: changing a student's email would otherwise clear the verification
    $student->forceFill([
        'student_id_number' => $schoolId, 'first_name' => $first, 'last_name' => $last, 'gender' => 'Female', 'about_me' => "I am {$first} and I like building apps.",
        'program' => 'BS Information Technology', 'year_level' => '4th Year', 'school_year' => '2026-2027',
        'date_of_birth' => '2003-06-10', 'place_of_birth' => 'Cebu City', 'address' => $place, 'city' => 'Minglanilla', 'province' => 'Cebu', 'zip_code' => '6046',
        'civil_status' => 'Single', 'religion' => 'Roman Catholic', 'linkedin_url' => 'https://linkedin.com/in/'.strtolower($first),
        'emergency_contact_name' => 'Maria '.$last, 'emergency_contact_relationship' => 'Mother', 'emergency_contact_address' => $place, 'emergency_contact_phone' => $emergencyPhone,
        'profile_picture' => "profiles/{$schoolId}.jpg", 'id_photo_path' => "id-photos/{$schoolId}.jpg", 'privacy_consent' => true, 'privacy_consent_at' => now(),
        'rendered_internship_hours' => 120,
    ])->save();

    $files = [
        'public' => ["avatars/{$schoolId}.png", "profiles/{$schoolId}.jpg", "certificates/{$schoolId}/c.png"],
        'private' => ["id-photos/{$schoolId}.jpg", "portfolios/{$schoolId}/p.pdf", "resumes/{$schoolId}.pdf", "competencies/{$schoolId}/e.pdf"],
        'local' => ["message-attachments/{$schoolId}.png"],
    ];
    foreach ($files as $disk => $paths) {
        foreach ($paths as $path) {
            Storage::disk($disk)->put($path, "file of {$schoolId}");
        }
    }

    Certificate::create(['student_id' => $student->id, 'title' => 'Web Foundations', 'file_path' => $files['public'][2], 'verification_status' => 'verified']);
    Portfolio::create(['student_id' => $student->id, 'title' => 'Capstone', 'type' => 'project', 'file_path' => $files['private'][1], 'verification_status' => 'verified']);
    $resume = Resume::create(['student_id' => $student->id, 'file_path' => $files['private'][2], 'content' => ['name' => $name, 'contact' => ['Mobile' => $phone]], 'generated_at' => now()]);
    StudentCompetency::create(['student_id' => $student->id, 'name' => 'JavaScript', 'category' => CompetencyCategory::Technical, 'proficiency_level' => ProficiencyLevel::Intermediate, 'verification_status' => 'verified', 'evidence_path' => $files['private'][3]]);
    StudentSkill::create(['student_id' => $student->id, 'skill_tag_id' => SkillTag::create(['name' => 'Laravel '.crc32($schoolId)])->id, 'level' => 'advanced']);
    CharacterReference::create(['student_id' => $student->id, 'name' => 'Engr. Reyes', 'position' => 'Manager', 'organization' => 'Acme', 'contact_number' => '09061112222']);
    DB::table('student_experiences')->insert(['student_id' => $student->id, 'organization' => 'Freelance', 'position' => 'Web dev', 'start_date' => '2025-01-01', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('student_change_requests')->insert(['student_id' => $student->id, 'field' => 'phone', 'current_value' => $phone, 'requested_value' => '09999999999', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('internship_applications')->where('student_id', $student->id)->update(['cover_letter' => "Dear sir, my name is {$first}...", 'employer_notes' => "Call {$first} on {$phone}", 'resume_id' => $resume->id]);

    foreach ([['2026-02-02', 8], ['2026-02-03', 8.5]] as [$date, $hours]) {
        $placement->attendanceLogs()->create(['work_date' => $date, 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'hours' => $hours, 'task_summary' => 'Coding', 'status' => 'approved']);
    }
    $header = ['full_name' => $name, 'student_id' => $schoolId, 'course' => 'BS IT', 'year_level' => '4th Year', 'contact_number' => $phone, 'address' => $place, 'company' => 'Acme', 'supervisor_name' => 'Sam Supervisor'];
    JournalEntry::create(['placement_id' => $placement->id, 'week_start' => '2026-02-02', 'week_number' => 1, 'title' => 'Week 1', 'accomplishments' => 'Built a form', 'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => JournalEntry::STATUS_SUBMITTED, 'header_snapshot' => $header]);
    Appraisal::create(['placement_id' => $placement->id, 'period_month' => '2026-02-01', 'period_label' => 'Midterm', 'period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'due_on' => '2026-03-05', 'header_snapshot' => $header]);
    Evaluation::create(['placement_id' => $placement->id, 'form_version' => 2, 'scores' => [], 'average_score' => 4.5, 'comments' => "Excellent intern: {$name} finished every task.", 'strengths' => "{$name} is reliable", 'submitted_at' => now(), 'header_snapshot' => $header]);
    $placement->attendanceLogs()->first()->update(['reviewer_remarks' => "Approved. ".strtoupper($name)." was on time."]);
    // Notices to staff and a queued email that name the student.
    app(NotificationService::class)->send(User::factory()->create(['role' => UserRole::Coordinator]), 'ojt_update', "{$name} submitted a weekly report", "{$name} sent Week 1", ['student' => $name], false, false);
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => json_encode(['displayName' => 'Mail', 'data' => ['command' => 'O:8:"Notice":1:{s:4:"body";s:30:"Hello '.$name.' your report";}']]), 'attempts' => 0, 'available_at' => now()->getTimestamp(), 'created_at' => now()->getTimestamp()]);

    $other = User::factory()->create(['role' => UserRole::Coordinator]);
    Message::create(['sender_id' => $user->id, 'receiver_id' => $other->id, 'body' => "my phone is {$phone}"]);
    Message::create(['sender_id' => $other->id, 'receiver_id' => $user->id, 'body' => 'noted', 'attachment_path' => $files['local'][0], 'attachment_name' => 'pic.png']);
    app(NotificationService::class)->send($user, 'ojt_update', 'Hello', "Hello {$first}", [], false, false);
    DB::table('sessions')->insert(['id' => 'sess-'.$schoolId, 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->getTimestamp()]);

    return compact('user', 'student', 'placement', 'files', 'other', 'company');
}

/* ---------- Asking for deletion ---------- */

it('lets a student ask for deletion, tells the admins, allows one request at a time, and lets them cancel', function () {
    adEnvironment();
    $admin = adAdmin();
    $s = adStudent();

    $this->actingAs($s['user'])->get(route('student.privacy.index'))->assertOk()->assertSee('Delete my account')->assertSee('cannot be undone');

    // Needs the password and the confirmation.
    $this->post(route('student.privacy.deletion-request'), ['password' => 'wrong', 'acknowledge' => '1'])->assertSessionHasErrors('password');
    $this->post(route('student.privacy.deletion-request'), ['password' => AD_PASSWORD])->assertSessionHasErrors('acknowledge');
    expect(AccountDeletionRequest::count())->toBe(0);

    $this->post(route('student.privacy.deletion-request'), ['password' => AD_PASSWORD, 'acknowledge' => '1', 'reason' => 'I am leaving'])->assertRedirect(route('student.privacy.index'));
    $request = AccountDeletionRequest::sole();
    expect($request->status)->toBe('pending')->and($request->source)->toBe('student')->and($request->reason)->toBe('I am leaving')->and($request->user_id)->toBe($s['user']->id);
    expect(AppNotification::where('user_id', $admin->id)->where('type', 'deletion_request')->count())->toBe(1);
    $this->assertDatabaseHas('system_logs', ['user_id' => $s['user']->id, 'action' => 'deletion_requested']);

    // The account works as usual while it waits; a second request is refused.
    $this->get(route('student.privacy.index'))->assertSee('data-deletion-pending', false)->assertSee('waiting for an administrator');
    $this->post(route('student.privacy.deletion-request'), ['password' => AD_PASSWORD, 'acknowledge' => '1'])->assertSessionHas('error');
    expect(AccountDeletionRequest::count())->toBe(1);
    expect(AppNotification::where('type', 'deletion_request')->first()->message)->not->toContain('Hazel'); // the notice outlives the account: no name in it

    $this->delete(route('student.privacy.deletion-cancel'))->assertRedirect();
    expect($request->fresh()->status)->toBe('cancelled');
    $this->delete(route('student.privacy.deletion-cancel'))->assertSessionHas('error'); // nothing left to cancel
});

it('warns about an OJT in progress, for the student and the admin', function () {
    adEnvironment();
    $s = adStudent();
    expect($s['placement']->status)->toBe(Placement::STATUS_ACTIVE);

    $this->actingAs($s['user'])->get(route('student.privacy.index'))->assertSee('data-active-ojt-warning', false);
    $this->post(route('student.privacy.deletion-request'), ['password' => AD_PASSWORD, 'acknowledge' => '1']);
    $this->actingAs(adAdmin())->get(route('admin.deletion-requests.index'))->assertOk()->assertSee('data-active-ojt-warning', false)->assertSee('OJT in progress');
});

it('keeps the queue and the decisions to administrators', function () {
    adEnvironment();
    $s = adStudent();
    $request = AccountDeletionRequest::create(['user_id' => $s['user']->id, 'requested_by_user_id' => $s['user']->id]);

    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Employer, UserRole::Supervisor] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('admin.deletion-requests.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.deletion-requests.approve', $request), ['password' => 'x', 'confirm' => '1'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.deletion-requests.reject', $request))->assertForbidden();
        $this->actingAs($user)->get(route('admin.retention'))->assertForbidden();
    }
    // The student pages are the students' own.
    $this->actingAs(User::factory()->create(['role' => UserRole::Coordinator]))->get(route('student.privacy.index'))->assertForbidden();
    $this->post('/logout');
    $this->get(route('admin.deletion-requests.index'))->assertRedirect(route('login'));
    expect($request->fresh()->status)->toBe('pending')->and(User::find($s['user']->id)->anonymised_at)->toBeNull();
});

it('rejects a request, keeps the account, and tells the student', function () {
    adEnvironment();
    $admin = adAdmin();
    $s = adStudent();
    $request = AccountDeletionRequest::create(['user_id' => $s['user']->id, 'requested_by_user_id' => $s['user']->id, 'reason' => 'test']);

    $this->actingAs($admin)->post(route('admin.deletion-requests.reject', $request), ['note' => 'Please finish your OJT first.'])->assertRedirect(route('admin.deletion-requests.index'));

    $request->refresh();
    expect($request->status)->toBe('rejected')->and($request->decided_by_user_id)->toBe($admin->id)->and($request->decision_note)->toContain('finish your OJT');
    expect(User::find($s['user']->id)->anonymised_at)->toBeNull()->and(User::find($s['user']->id)->email)->toBe('hazel@school.test');
    $notice = AppNotification::where('user_id', $s['user']->id)->where('type', 'deletion_decision')->first();
    expect($notice)->not->toBeNull()->and($notice->message)->toContain('finish your OJT');
});

/* ---------- Approving: backup first, then anonymise ---------- */

it('makes an encrypted backup first, then anonymises, and records both', function () {
    $sawIntactData = null;
    $s = adStudent();
    adEnvironment(function () use (&$sawIntactData, $s) {
        // The moment the backup dumps the database, the student must still be whole.
        $sawIntactData = User::find($s['user']->id)->email === 'hazel@school.test' && Storage::disk('private')->exists($s['files']['private'][0]);
    });
    foreach ($s['files'] as $disk => $paths) {
        foreach ($paths as $path) {
            Storage::disk($disk)->put($path, 'x'); // adEnvironment faked the disks afresh
        }
    }
    $admin = adAdmin();
    $request = AccountDeletionRequest::create(['user_id' => $s['user']->id, 'requested_by_user_id' => $s['user']->id, 'reason' => 'Name: Hazel Villaflor']);

    // Needs the admin's password and the confirmation.
    $this->actingAs($admin)->post(route('admin.deletion-requests.approve', $request), ['password' => 'wrong', 'confirm' => '1'])->assertSessionHasErrors('password');
    $this->post(route('admin.deletion-requests.approve', $request), ['password' => AD_PASSWORD])->assertSessionHasErrors('confirm');
    expect($request->fresh()->status)->toBe('pending')->and(User::find($s['user']->id)->email)->toBe('hazel@school.test');

    $this->post(route('admin.deletion-requests.approve', $request), ['password' => AD_PASSWORD, 'confirm' => '1'])->assertRedirect(route('admin.deletion-requests.index'))->assertSessionHas('success');

    expect($sawIntactData)->toBeTrue('the backup must be made before anything is removed');
    $request->refresh();
    expect($request->status)->toBe('approved')->and($request->decided_by_user_id)->toBe($admin->id)->and($request->reason)->toBeNull()
        ->and($request->backup_name)->toMatch('/^backup_.*\.zip$/')
        ->and($request->summary['hours_kept'])->toBe(16.5)->and($request->summary['placements_kept'])->toBe(1);
    // That backup is a real, encrypted archive and it exists.
    Storage::disk('local')->assertExists('backups/'.$request->backup_name);
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path('backups/'.$request->backup_name));
    expect($zip->getFromName('database.sql'))->toBeFalse(); // no password: cannot be read
    $zip->close();

    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'deletion_approved']);
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'account_anonymised']);
    $this->get(route('admin.deletion-requests.index'))->assertOk()->assertSee($request->backup_name)->assertDontSee('hazel@school.test');

    // Deciding twice does nothing more.
    $this->post(route('admin.deletion-requests.approve', $request), ['password' => AD_PASSWORD, 'confirm' => '1'])->assertSessionHas('error');
});

it('deletes nothing when the backup cannot be made', function () {
    $s = adStudent();
    adEnvironment(failBackup: true);
    foreach ($s['files'] as $disk => $paths) {
        foreach ($paths as $path) {
            Storage::disk($disk)->put($path, 'x');
        }
    }
    $request = AccountDeletionRequest::create(['user_id' => $s['user']->id, 'requested_by_user_id' => $s['user']->id]);

    $this->actingAs(adAdmin())->post(route('admin.deletion-requests.approve', $request), ['password' => AD_PASSWORD, 'confirm' => '1'])
        ->assertRedirect()->assertSessionHas('error', fn (string $m) => str_contains($m, 'nothing was deleted'));

    expect($request->fresh()->status)->toBe('pending')
        ->and(User::find($s['user']->id)->email)->toBe('hazel@school.test')->and(User::find($s['user']->id)->anonymised_at)->toBeNull()
        ->and(Student::find($s['student']->id)->address)->toBe('Tunghaan, Minglanilla')
        ->and(Storage::disk('private')->exists($s['files']['private'][0]))->toBeTrue();

    // And without a backup password nothing is deleted either.
    adEnvironment();
    config(['backup.password' => null]);
    $this->post(route('admin.deletion-requests.approve', $request), ['password' => AD_PASSWORD, 'confirm' => '1'])->assertSessionHas('error', fn (string $m) => str_contains($m, 'BACKUP_PASSWORD'));
    expect(User::find($s['user']->id)->anonymised_at)->toBeNull();
});

/* ---------- What anonymising removes and keeps ---------- */

it('removes every trace of the student, keeps the OJT without a name, and leaves other students alone', function () {
    adEnvironment();
    $s = adStudent();
    $bystander = adStudent('Jericho Pepito', 'jericho@school.test', 'SCC-23-00024001', '09175550000', '09186660000', 'Poblacion, Talisay');
    $admin = adAdmin();

    // Audit entries that hold the student's identifiers: one about them, one that only mentions what was typed, and an unrelated one.
    $log = app(\App\Services\ActivityLogService::class);
    $log->log($s['user'], 'login_failed', ['login' => 'hazel@school.test', 'reason' => 'wrong_password']);
    $log->log(null, 'login_failed', ['login' => 'SCC-23-00024040', 'reason' => 'wrong_password']);
    $log->log($admin, 'admin_import_student', ['user_id' => $s['user']->id, 'student_id_number' => 'SCC-23-00024040']);
    $log->log($bystander['user'], 'login_failed', ['login' => 'jericho@school.test', 'reason' => 'wrong_password']);

    $summary = app(AccountAnonymizer::class)->anonymise($s['user'], $admin);

    // --- the account
    $user = User::find($s['user']->id);
    expect($user->name)->toBe('Former student')->and($user->email)->toBeNull()->and($user->avatar)->toBeNull()
        ->and($user->is_active)->toBeFalse()->and($user->anonymised_at)->not->toBeNull()->and($user->archived_at)->not->toBeNull()
        ->and(Hash::check(AD_PASSWORD, $user->password))->toBeFalse()
        ->and(DB::table('users')->where('id', $user->id)->value('phone'))->toBeNull();
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    // --- the student record: personal fields are empty in the table, program and OJT hours stay
    $raw = DB::table('students')->where('id', $s['student']->id)->first();
    foreach (['first_name', 'last_name', 'gender', 'about_me', 'date_of_birth', 'place_of_birth', 'address', 'city', 'province', 'zip_code', 'civil_status', 'religion', 'linkedin_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone', 'profile_picture', 'id_photo_path', 'student_id_number'] as $column) {
        expect($raw->{$column})->toBeNull("students.{$column} must be cleared");
    }
    expect($raw->program)->toBe('BS Information Technology')->and($raw->year_level)->toBe('4th Year')->and((int) $raw->rendered_internship_hours)->toBe(120)->and((int) $raw->privacy_consent)->toBe(0);

    // --- rows that only describe the person are gone, with their files
    foreach (['certificates', 'portfolios', 'resumes', 'student_competencies', 'student_skills', 'character_references', 'student_experiences', 'student_change_requests'] as $table) {
        expect(DB::table($table)->where('student_id', $s['student']->id)->count())->toBe(0, "{$table} must be empty for the student");
    }
    foreach ($s['files'] as $disk => $paths) {
        foreach ($paths as $path) {
            expect(Storage::disk($disk)->exists($path))->toBeFalse("{$disk}:{$path} must be deleted");
        }
    }
    expect(Message::where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->count())->toBe(0)
        ->and(AppNotification::where('user_id', $user->id)->count())->toBe(0);

    // --- the OJT stays, now anonymous
    $placement = Placement::find($s['placement']->id);
    expect($placement)->not->toBeNull()->and((float) $placement->required_hours)->toBe(500.0)->and($placement->attendanceLogs()->count())->toBe(2)
        ->and((float) $placement->attendanceLogs()->sum('hours'))->toBe(16.5)
        ->and(JournalEntry::where('placement_id', $placement->id)->first()->accomplishments)->toBe('Built a form')
        ->and(Evaluation::where('placement_id', $placement->id)->first()->comments)->toBe('Excellent intern: Former student finished every task.') // the comment stays, the name does not
        ->and(Evaluation::where('placement_id', $placement->id)->first()->strengths)->toBe('Former student is reliable')
        ->and($placement->attendanceLogs()->where('reviewer_remarks', 'like', 'Approved. Former student%')->count())->toBe(1)
        ->and((float) Evaluation::where('placement_id', $placement->id)->first()->average_score)->toBe(4.5);
    $application = DB::table('internship_applications')->where('student_id', $s['student']->id)->first();
    expect($application)->not->toBeNull()->and($application->cover_letter)->toBeNull()->and($application->employer_notes)->toBeNull(); // the application (and its placement) is not deleted
    foreach ([JournalEntry::where('placement_id', $placement->id)->first(), Appraisal::where('placement_id', $placement->id)->first(), Evaluation::where('placement_id', $placement->id)->first()] as $form) {
        $header = $form->header_snapshot;
        expect($header['full_name'])->toBe('Former student')->and($header['student_id'])->toBe('')->and($header['contact_number'])->toBe('')->and($header['address'])->toBe('')
            ->and($header['course'])->toBe('BS IT')->and($header['company'])->toBe('Acme')->and($header['supervisor_name'])->toBe('Sam Supervisor'); // only the student's identity goes
    }

    // --- nothing identifying is left anywhere in the database
    $everything = collect(DB::select("select name from sqlite_master where type = 'table'"))->pluck('name')
        ->reject(fn ($table) => in_array($table, ['migrations']))
        ->flatMap(fn ($table) => DB::table($table)->get()->map(fn ($row) => json_encode($row)))
        ->filter(fn (string $row) => preg_match('/Hazel|Villaflor|09240173281|09244473927|SCC-23-00024040|hazel@school\.test|Tunghaan/', $row));
    expect($everything->values()->all())->toBe([]); // no row in any table still holds the deleted student's name, number, email or address

    // The entries are still there (what happened), without who; the other student's entry is untouched.
    expect(\App\Models\SystemLog::where('action', 'login_failed')->count())->toBe(3)
        ->and(\App\Models\SystemLog::where('action', 'login_failed')->get()->pluck('details.login')->all())->toContain('[removed]', 'jericho@school.test')
        ->and($summary['log_entries_scrubbed'])->toBe(3);

    // --- the other student is untouched
    $other = User::find($bystander['user']->id);
    expect($other->name)->toBe('Jericho Pepito')->and($other->email)->toBe('jericho@school.test')->and($other->phone)->toBe('09175550000')
        ->and(Student::find($bystander['student']->id)->address)->toBe('Poblacion, Talisay')->and(Student::find($bystander['student']->id)->date_of_birth->toDateString())->toBe('2003-06-10')
        ->and(Certificate::where('student_id', $bystander['student']->id)->count())->toBe(1)
        ->and(Resume::where('student_id', $bystander['student']->id)->first()->content['name'])->toBe('Jericho Pepito')
        ->and(Evaluation::where('placement_id', $bystander['placement']->id)->first()->header_snapshot['full_name'])->toBe('Jericho Pepito');
    foreach ($bystander['files'] as $disk => $paths) {
        foreach ($paths as $path) {
            expect(Storage::disk($disk)->exists($path))->toBeTrue("{$disk}:{$path} of the other student must stay");
        }
    }
    // The coordinator's own side of the conversation is gone with it, the bystander's conversation is not.
    expect(Message::where('sender_id', $bystander['user']->id)->count())->toBe(1);

    // Notices to others name no one any more; the queued email is gone.
    expect(AppNotification::where('type', 'ojt_update')->get()->pluck('title')->implode('|'))->toContain('Former student submitted a weekly report')->not->toContain('Hazel')
        ->and(AppNotification::where('type', 'ojt_update')->first()->data)->toBe(['student' => 'Former student'])
        ->and(DB::table('jobs')->count())->toBe(1)->and(DB::table('jobs')->value('payload'))->toContain('Jericho'); // the bystander's job stays

    expect($summary['files_deleted'])->toBe(8)->and($summary['messages_deleted'])->toBe(2)->and($summary['removed']['certificates'])->toBe(1)->and($summary['snapshots_scrubbed'])->toBe(3)
        ->and($summary['placements_kept'])->toBe(1)->and($summary['hours_kept'])->toBe(16.5);
    // Anonymising twice, or a non-student, is refused.
    expect(fn () => app(AccountAnonymizer::class)->anonymise($user, $admin))->toThrow(InvalidArgumentException::class);
    expect(fn () => app(AccountAnonymizer::class)->anonymise($admin, $admin))->toThrow(InvalidArgumentException::class);
});

it('clears the cached dashboard copies that name the student, and nothing else', function () {
    adEnvironment();
    $s = adStudent();
    $employerId = $s['company']['employer']->id;
    $application = DB::table('internship_applications')->where('student_id', $s['student']->id)->first();
    $employerId = DB::table('internships')->where('id', $application->internship_id)->value('employer_id');
    foreach (['employer_recent_apps_'.$employerId, 'employer_all_apps_'.$employerId, 'student_recent_apps_'.$s['student']->id, 'admin_recent_users', 'admin_dashboard_stats'] as $key) {
        Cache::put($key, ['recent' => [['student' => 'Hazel Villaflor', 'email' => 'hazel@school.test']]], 300);
    }
    Cache::put('unrelated_key', 'kept', 300);

    app(AccountAnonymizer::class)->anonymise($s['user'], adAdmin());

    foreach (['employer_recent_apps_'.$employerId, 'employer_all_apps_'.$employerId, 'student_recent_apps_'.$s['student']->id, 'admin_recent_users', 'admin_dashboard_stats'] as $key) {
        expect(Cache::has($key))->toBeFalse("{$key} still holds the student's name");
    }
    expect(Cache::get('unrelated_key'))->toBe('kept');
});

it('hides an anonymised student from the coordinator directory and count, but keeps the OJT record visible', function () {
    adEnvironment();
    $s = adStudent();
    $other = adStudent('Jericho Pepito', 'jericho@school.test', 'SCC-23-00024001', '09175550000', '09186660000', 'Poblacion, Talisay');
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator->coordinator()->create(['institution_id' => $s['student']->institution_id]);
    assignDepartment($coordinator->coordinator, $s['student'], $other['student']);

    $this->actingAs($coordinator)->getJson(route('coordinator.students.index'))->assertOk()->assertJsonCount(2, 'students')->assertJsonPath('summary.total', 2);
    $this->actingAs($coordinator)->get(route('coordinator.placements.index'))->assertOk()->assertSee('Hazel Villaflor')->assertSee('Jericho Pepito');

    app(AccountAnonymizer::class)->anonymise($s['user'], adAdmin());
    Cache::flush();

    $this->actingAs($coordinator)->getJson(route('coordinator.students.index'))->assertOk()->assertJsonCount(1, 'students')->assertJsonPath('summary.total', 1)
        ->assertJsonMissing(['name' => 'Former student']);
    // The OJT record is still there for monitoring, with no name.
    $this->get(route('coordinator.placements.index'))->assertOk()->assertSee('Former student')->assertDontSee('Hazel Villaflor')->assertSee('Jericho Pepito');
});

/* ---------- A deleted student cannot come back ---------- */

it('cannot sign in, reset a password or keep a session once anonymised', function () {
    adEnvironment();
    $s = adStudent();
    app(AccountAnonymizer::class)->anonymise($s['user'], adAdmin());

    // By the old email and by the old school ID, with the old password: both look like unknown accounts.
    foreach (['hazel@school.test', 'SCC-23-00024040', 'scc-23-00024040'] as $login) {
        $this->post(route('login'), ['login' => $login, 'password' => AD_PASSWORD])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    // Even a stolen, still-valid session is refused and ended (an inactive account gets a 403 and is signed out).
    $this->actingAs(User::find($s['user']->id))->get(route('student.dashboard'))->assertForbidden();
    $this->assertGuest();

    // A password reset for the old email finds nobody and sends nothing.
    \Illuminate\Support\Facades\Mail::fake();
    $this->post(route('password.email'), ['email' => 'hazel@school.test']);
    \Illuminate\Support\Facades\Mail::assertNothingSent();

    // Re-activating the row by hand still gives no way in: there is no email, no school ID and no known password.
    $user = User::find($s['user']->id);
    $user->forceFill(['is_active' => true, 'archived_at' => null])->save();
    $this->post(route('login'), ['login' => 'hazel@school.test', 'password' => AD_PASSWORD])->assertSessionHasErrors('login');
    expect($user->fresh()->email)->toBeNull()->and(Student::find($s['student']->id)->student_id_number)->toBeNull();
});
