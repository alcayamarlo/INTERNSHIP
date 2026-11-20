<?php

use App\Enums\UserRole;
use App\Models\CharacterReference;
use App\Models\Student;
use App\Models\User;
use App\Support\SensitiveColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(RefreshDatabase::class);

const ENC_STUDENT = [
    'date_of_birth' => '2003-05-14',
    'place_of_birth' => 'Cebu City',
    'address' => '12 Mabini Street, Barangay Poblacion',
    'city' => 'Minglanilla',
    'province' => 'Cebu',
    'zip_code' => '6046',
    'provincial_address' => 'Purok 4, Sitio Lahug, Bohol',
    'emergency_contact_name' => 'Maria Villaflor',
    'emergency_contact_relationship' => 'Mother',
    'emergency_contact_address' => '12 Mabini Street, Minglanilla',
    'emergency_contact_phone' => '09171234567',
    'religion' => 'Roman Catholic',
    'civil_status' => 'Single',
];

/** A student, a referee and a user phone made through the models, like the app does. */
function encPerson(): array
{
    $user = User::factory()->create(['role' => UserRole::Student, 'phone' => '09285550123']);
    $student = Student::create(['user_id' => $user->id] + ENC_STUDENT);
    $reference = CharacterReference::create(['student_id' => $student->id, 'name' => 'Engr. Reyes', 'position' => 'Manager', 'organization' => 'Acme', 'contact_number' => '09061112222']);

    return [$user, $student, $reference];
}

function encRaw(string $table, int $id): object
{
    return DB::table($table)->where('id', $id)->first();
}

function encMigration(): object
{
    return require base_path('database/migrations/2026_11_26_000001_encrypt_sensitive_personal_data.php');
}

/* ---------- Stored encrypted, read correctly ---------- */

it('stores the personal data encrypted and reads it back unchanged through the models', function () {
    [$user, $student, $reference] = encPerson();

    $rawStudent = encRaw('students', $student->id);
    foreach (ENC_STUDENT as $column => $plain) {
        expect(SensitiveColumns::looksEncrypted($rawStudent->{$column}))->toBeTrue("students.{$column} must be encrypted")
            ->and($rawStudent->{$column})->not->toContain($plain)
            ->and(Crypt::decryptString($rawStudent->{$column}))->toBe($plain); // the stored text is the plain value
    }
    expect(SensitiveColumns::looksEncrypted(encRaw('users', $user->id)->phone))->toBeTrue()
        ->and(encRaw('users', $user->id)->phone)->not->toContain('09285550123')
        ->and(SensitiveColumns::looksEncrypted(encRaw('character_references', $reference->id)->contact_number))->toBeTrue();

    // Through the models, as the app reads them.
    $student = Student::find($student->id);
    foreach (ENC_STUDENT as $column => $plain) {
        if ($column !== 'date_of_birth') {
            expect($student->{$column})->toBe($plain);
        }
    }
    expect($student->date_of_birth)->toBeInstanceOf(\Carbon\CarbonInterface::class)
        ->and($student->date_of_birth->toDateString())->toBe('2003-05-14')
        ->and($student->date_of_birth->isStartOfDay())->toBeTrue()
        ->and($student->date_of_birth->age)->toBeGreaterThan(18)
        ->and(User::find($user->id)->phone)->toBe('09285550123')
        ->and(CharacterReference::find($reference->id)->contact_number)->toBe('09061112222');
    expect($student->toArray()['date_of_birth'])->toBeString()->toContain('2003')->and($student->toArray()['address'])->toBe(ENC_STUDENT['address']);
});

it('writes a birth date given as text, a date or a Carbon, and keeps empty values empty', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id, 'date_of_birth' => '2003-05-14']);
    expect(Student::find($student->id)->date_of_birth->toDateString())->toBe('2003-05-14');

    foreach ([new DateTime('2001-02-03 15:30:00'), \Carbon\Carbon::parse('2001-02-03 23:59:59'), '2001-02-03'] as $input) {
        $student->update(['date_of_birth' => $input]);
        expect(Student::find($student->id)->date_of_birth->toDateString())->toBe('2001-02-03');
    }

    $student->update(['date_of_birth' => null, 'address' => null, 'religion' => null]);
    $raw = encRaw('students', $student->id);
    expect($raw->date_of_birth)->toBeNull()->and($raw->address)->toBeNull()->and($raw->religion)->toBeNull()
        ->and(Student::find($student->id)->date_of_birth)->toBeNull();
});

it('does not see a change when the same values are saved again', function () {
    [, $student] = encPerson();
    $student = Student::find($student->id);
    $before = encRaw('students', $student->id);
    $updatedAt = $student->updated_at;

    $this->travel(5)->minutes();
    $student->fill(ENC_STUDENT);
    expect($student->isDirty())->toBeFalse();
    $student->save();
    expect(encRaw('students', $student->id)->updated_at)->toBe($before->updated_at)
        ->and(encRaw('students', $student->id)->address)->toBe($before->address); // not even re-encrypted

    $student->date_of_birth = '2003-05-14';
    $student->city = 'Cebu';
    expect($student->isDirty('date_of_birth'))->toBeFalse()->and($student->isDirty('city'))->toBeTrue();
    $student->date_of_birth = '2003-05-15';
    expect($student->isDirty('date_of_birth'))->toBeTrue();
});

it('writes through the models in every place the app saves this data', function () {
    // The student's own profile form (its contact and personal sections).
    $user = User::factory()->create(['role' => UserRole::Student, 'phone' => '09170000000']);
    $student = Student::create(['user_id' => $user->id, 'privacy_consent' => true, 'privacy_consent_at' => now()]);
    $this->actingAs($user);
    $this->putJson(route('student.profile.update'), [
        'profile_section' => 'contact', 'email' => $user->email, 'phone' => '0917 123 4567',
        'address' => '99 Rizal Ave', 'city' => 'Cebu City', 'province' => 'Cebu', 'zip_code' => '6000',
        'emergency_contact_name' => 'Ana Cruz', 'emergency_contact_relationship' => 'Aunt',
        'emergency_contact_address' => '5 Osmena Blvd', 'emergency_contact_phone' => '09181112222',
    ])->assertOk();
    $this->putJson(route('student.profile.update'), ['profile_section' => 'personal', 'first_name' => 'Ana', 'last_name' => 'Cruz', 'gender' => 'Female', 'date_of_birth' => '2002-08-09', 'place_of_birth' => 'Talisay'])->assertOk();

    $rawStudent = encRaw('students', $student->id);
    foreach (['date_of_birth', 'place_of_birth', 'address', 'city', 'province', 'zip_code', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone'] as $column) {
        expect(SensitiveColumns::looksEncrypted($rawStudent->{$column}))->toBeTrue("students.{$column} must be encrypted after the form saved it");
    }
    expect(SensitiveColumns::looksEncrypted(encRaw('users', $user->id)->phone))->toBeTrue()
        ->and(User::find($user->id)->phone)->not->toBeNull()
        ->and(Student::find($student->id)->address)->toBe('99 Rizal Ave')
        ->and(Student::find($student->id)->date_of_birth->toDateString())->toBe('2002-08-09')
        ->and(Student::find($student->id)->emergency_contact_phone)->toBe('09181112222');
    // A character reference.
    $this->post(route('student.references.store'), ['name' => 'Dr. Lim', 'position' => 'Dean', 'organization' => 'SCC', 'contact_number' => '09175559999']);
    $reference = CharacterReference::where('student_id', $student->id)->first();
    expect($reference)->not->toBeNull()->and($reference->contact_number)->toBe('09175559999')
        ->and(SensitiveColumns::looksEncrypted(encRaw('character_references', $reference->id)->contact_number))->toBeTrue();
    $this->get(route('student.profile.edit'))->assertOk()->assertSee('09175559999')->assertSee('99 Rizal Ave');
});

/* ---------- The migration ---------- */

it('decrypts in down() and encrypts again in up(), and up() can run twice', function () {
    [$user, $student, $reference] = encPerson();
    $migration = encMigration();

    $migration->down();
    $plainStudent = encRaw('students', $student->id);
    foreach (ENC_STUDENT as $column => $plain) {
        expect($plainStudent->{$column})->toBe($plain, "students.{$column} must be plain text after down()");
    }
    expect(encRaw('users', $user->id)->phone)->toBe('09285550123')
        ->and(encRaw('character_references', $reference->id)->contact_number)->toBe('09061112222');

    $migration->up();
    $once = encRaw('students', $student->id);
    foreach (ENC_STUDENT as $column => $plain) {
        expect(SensitiveColumns::looksEncrypted($once->{$column}))->toBeTrue()->and(Crypt::decryptString($once->{$column}))->toBe($plain);
    }
    expect(Student::find($student->id)->date_of_birth->toDateString())->toBe('2003-05-14')
        ->and(Student::find($student->id)->emergency_contact_phone)->toBe('09171234567')
        ->and(User::find($user->id)->phone)->toBe('09285550123')
        ->and(CharacterReference::find($reference->id)->contact_number)->toBe('09061112222');

    $migration->up(); // already encrypted: nothing is encrypted a second time
    $twice = encRaw('students', $student->id);
    foreach (ENC_STUDENT as $column => $plain) {
        expect($twice->{$column})->toBe($once->{$column});
    }
    expect($twice->updated_at)->toBe($once->updated_at);
});

it('encrypts plain rows in up(), including dates stored with a time and empty values', function () {
    $migration = encMigration();
    $migration->down();
    $user = User::factory()->create(['role' => UserRole::Student, 'phone' => null]);
    DB::table('users')->where('id', $user->id)->update(['phone' => '+63 917 000 0000']);
    $studentId = DB::table('students')->insertGetId(['user_id' => $user->id, 'date_of_birth' => '1999-12-31', 'address' => '', 'city' => 'Cebu', 'created_at' => now(), 'updated_at' => now()]);

    $migration->up();

    $raw = encRaw('students', $studentId);
    expect(Crypt::decryptString($raw->city))->toBe('Cebu')
        ->and(Crypt::decryptString($raw->address))->toBe('')   // an empty text is kept, encrypted
        ->and($raw->province)->toBeNull()                       // null stays null
        ->and(Student::find($studentId)->date_of_birth->toDateString())->toBe('1999-12-31')
        ->and(User::find($user->id)->phone)->toBe('+63 917 000 0000');
});

it('refuses to run down() when a value cannot be decrypted or would not fit, and changes nothing', function () {
    [, $student] = encPerson();
    [, $other] = encPerson();
    $migration = encMigration();
    $snapshot = fn () => json_encode([encRaw('students', $student->id), encRaw('students', $other->id), DB::table('users')->pluck('phone', 'id')]);
    $before = $snapshot();

    // A payload made with another key: it has the shape of an encrypted value but cannot be read.
    $foreign = base64_encode(json_encode(['iv' => base64_encode(random_bytes(16)), 'value' => base64_encode(random_bytes(24)), 'mac' => bin2hex(random_bytes(32))]));
    DB::table('students')->where('id', $other->id)->update(['city' => $foreign]);
    $before = $snapshot();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Cannot decrypt students.city');
    expect($snapshot())->toBe($before);

    // A value longer than the original column (here the 30 characters of the emergency phone).
    DB::table('students')->where('id', $other->id)->update(['city' => Crypt::encryptString('Cebu')]);
    Student::find($other->id)->update(['emergency_contact_phone' => str_repeat('9', 31)]);
    $before = $snapshot();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'longer than the original 30');
    expect($snapshot())->toBe($before);
});

/* ---------- data:reencrypt ---------- */

it('reports plain or unreadable values with --check, and repairs plain ones', function () {
    [$user, $student] = encPerson();

    $this->artisan('data:reencrypt', ['--check' => true])->expectsOutputToContain('All personal data is encrypted')->assertSuccessful();

    DB::table('students')->where('id', $student->id)->update(['city' => 'Plain City', 'date_of_birth' => '2003-05-14 00:00:00']);
    $this->artisan('data:reencrypt', ['--check' => true])->expectsOutputToContain('plain text')->assertFailed();
    expect(encRaw('students', $student->id)->city)->toBe('Plain City'); // --check changes nothing

    $this->artisan('data:reencrypt')->expectsOutputToContain('every value is encrypted')->assertSuccessful();
    expect(SensitiveColumns::looksEncrypted(encRaw('students', $student->id)->city))->toBeTrue()
        ->and(Student::find($student->id)->city)->toBe('Plain City')
        ->and(Student::find($student->id)->date_of_birth->toDateString())->toBe('2003-05-14');
    $this->artisan('data:reencrypt', ['--check' => true])->assertSuccessful();

    // An unreadable value is reported and never overwritten.
    $foreign = base64_encode(json_encode(['iv' => base64_encode(random_bytes(16)), 'value' => base64_encode(random_bytes(24)), 'mac' => bin2hex(random_bytes(32))]));
    DB::table('users')->where('id', $user->id)->update(['phone' => $foreign]);
    $this->artisan('data:reencrypt')->assertFailed();
    expect(encRaw('users', $user->id)->phone)->toBe($foreign);
});

it('re-encrypts with the new key after a rotation, so the old key can be dropped', function () {
    [$user, $student] = encPerson();
    $oldKey = config('app.key');
    $newKey = 'base64:'.base64_encode(random_bytes(32));
    $rotate = function (string $key, array $previous) {
        config(['app.key' => $key, 'app.previous_keys' => $previous]);
        app()->forgetInstance('encrypter');
        \Illuminate\Support\Facades\Facade::clearResolvedInstance('encrypter');
        \Illuminate\Database\Eloquent\Model::encryptUsing(null);
    };

    // New key, old one kept in APP_PREVIOUS_KEYS: everything still reads.
    $rotate($newKey, [$oldKey]);
    expect(Student::find($student->id)->address)->toBe(ENC_STUDENT['address'])->and(User::find($user->id)->phone)->toBe('09285550123');
    $this->artisan('data:reencrypt', ['--check' => true])->assertSuccessful();

    $this->artisan('data:reencrypt')->assertSuccessful();

    // Old key removed: only what was re-encrypted with the new key remains readable, and all of it is.
    $rotate($newKey, []);
    expect(Student::find($student->id)->address)->toBe(ENC_STUDENT['address'])
        ->and(Student::find($student->id)->date_of_birth->toDateString())->toBe('2003-05-14')
        ->and(User::find($user->id)->phone)->toBe('09285550123');
    $this->artisan('data:reencrypt', ['--check' => true])->assertSuccessful();

    // The old key alone can no longer read it: that is why it was re-encrypted before the old key was dropped.
    $oldEncrypter = new Illuminate\Encryption\Encrypter(base64_decode(substr($oldKey, 7)), config('app.cipher'));
    expect(fn () => $oldEncrypter->decryptString(encRaw('users', $user->id)->phone))->toThrow(Illuminate\Contracts\Encryption\DecryptException::class);
});

/* ---------- Safety nets ---------- */

it('lists every encrypted column in the models and in the support list', function () {
    $models = ['students' => new Student, 'users' => new User, 'character_references' => new CharacterReference, 'resumes' => new \App\Models\Resume, 'journal_entries' => new \App\Models\JournalEntry, 'appraisals' => new \App\Models\Appraisal, 'evaluations' => new \App\Models\Evaluation];
    foreach (SensitiveColumns::COLUMNS as $table => $columns) {
        foreach ($columns as $column) {
            $cast = $models[$table]->getCasts()[$column] ?? null;
            expect($cast)->toBeIn(['encrypted', 'encrypted:array', \App\Casts\EncryptedDate::class], "{$table}.{$column} must be an encrypted cast");
        }
    }
});

it('refuses to run tests against anything but an in-memory SQLite database', function () {
    $ok = ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:']]];
    TestCase::assertDisposableDatabase($ok); // does not throw

    foreach ([
        ['default' => 'mysql', 'connections' => ['mysql' => ['driver' => 'mysql', 'database' => 'nexus']]],
        ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => database_path('database.sqlite')]]],
        ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => '']]],
        ['default' => 'missing', 'connections' => []],
    ] as $config) {
        expect(fn () => TestCase::assertDisposableDatabase($config))->toThrow(RuntimeException::class, 'Refusing to run tests');
    }
    // And this very run is on the safe database.
    expect(config('database.default'))->toBe('sqlite')->and(config('database.connections.sqlite.database'))->toBe(':memory:');
});
