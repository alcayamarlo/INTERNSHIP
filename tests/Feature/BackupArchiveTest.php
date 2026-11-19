<?php

use App\Enums\UserRole;
use App\Jobs\ScheduledBackup;
use App\Models\User;
use App\Services\BackupArchive;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const BA_PASSWORD = 'a-long-backup-password-for-tests-0123';

/** Fake disks with a few uploaded files, a fake database dump and a known backup password. */
function baSetup(): void
{
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('private');
    Storage::disk('public')->put('avatars/student/1.png', 'a student picture');
    Storage::disk('public')->put('certificates/5/web-development.png', 'a certificate');
    Storage::disk('private')->put('resumes/9.pdf', '%PDF resume');
    Storage::disk('private')->put('backups/backup_2026-01-01_000000.zip', 'an older backup'); // never goes into a new backup
    config(['backup.password' => BA_PASSWORD, 'backup.copy_path' => null, 'backup.keep' => 30]);
    test()->mock(DatabaseBackup::class, fn ($mock) => $mock->shouldReceive('dumpTo')->andReturnUsing(
        fn (string $path) => file_put_contents($path, "-- MySQL dump\nINSERT INTO students VALUES (1,'Hazel');\n")
    ));
}

function baOpen(string $path, ?string $password): ZipArchive
{
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    if ($password !== null) {
        $zip->setPassword($password);
    }

    return $zip;
}

it('makes one encrypted archive with the database, every uploaded file and a manifest', function () {
    baSetup();
    User::factory()->create(['role' => UserRole::Student]);

    $result = app(BackupArchive::class)->create();

    expect($result['name'])->toMatch('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.zip$/')
        ->and($result['files'])->toBe(3)
        ->and($result['size'])->toBeGreaterThan(0)
        ->and($result['sha256'])->toBe(hash_file('sha256', $result['path']))
        ->and(is_file($result['path']))->toBeTrue();

    // With the password everything is there; without it nothing can be read.
    $zip = baOpen($result['path'], BA_PASSWORD);
    expect($zip->getFromName('database.sql'))->toContain("INSERT INTO students VALUES (1,'Hazel')")
        ->and($zip->getFromName('files/public/avatars/student/1.png'))->toBe('a student picture')
        ->and($zip->getFromName('files/public/certificates/5/web-development.png'))->toBe('a certificate')
        ->and($zip->getFromName('files/private/resumes/9.pdf'))->toBe('%PDF resume');
    expect($zip->locateName('files/private/backups/backup_2026-01-01_000000.zip'))->toBeFalse();
    $manifest = json_decode($zip->getFromName('manifest.json'), true);
    expect($manifest['files'])->toBe(3)->and($manifest['rows']['users'])->toBe(1);
    $zip->close();

    foreach ([null, 'the-wrong-password-entirely'] as $wrong) {
        $zip = baOpen($result['path'], $wrong);
        expect($zip->getFromName('database.sql'))->toBeFalse()->and($zip->getFromName('files/private/resumes/9.pdf'))->toBeFalse();
        $zip->close();
    }
    // The raw file does not contain the data in the clear.
    expect(file_get_contents($result['path']))->not->toContain('Hazel')->not->toContain('a student picture');
});

it('leaves no unencrypted dump behind', function () {
    baSetup();

    app(BackupArchive::class)->create();

    expect(Storage::disk('local')->files('backups'))->toHaveCount(1)
        ->and(collect(Storage::disk('local')->allFiles('backups'))->contains(fn (string $file) => str_ends_with($file, '.sql') || str_ends_with($file, '.tmp')))->toBeFalse();
});

it('never makes a backup without a strong password, and discards one that fails its check', function () {
    baSetup();

    foreach ([null, '', 'too-short'] as $weak) {
        config(['backup.password' => $weak]);
        expect(fn () => app(BackupArchive::class)->create())->toThrow(RuntimeException::class, 'BACKUP_PASSWORD');
    }
    expect(Storage::disk('local')->files('backups'))->toBe([]);

    // A dump that comes out empty is refused (and its archive removed).
    config(['backup.password' => BA_PASSWORD]);
    $this->mock(DatabaseBackup::class, fn ($mock) => $mock->shouldReceive('dumpTo')->andReturnUsing(fn (string $path) => file_put_contents($path, '')));
    expect(fn () => app(BackupArchive::class)->create())->toThrow(RuntimeException::class, 'failed its check')
        ->and(Storage::disk('local')->files('backups'))->toBe([]);
});

it('copies the archive to a second folder and keeps only the newest 30 in each place', function () {
    baSetup();
    $copy = storage_path('framework/testing/backup-copy-'.uniqid());
    config(['backup.copy_path' => $copy, 'backup.keep' => 3]);
    mkdir($copy, 0755, true);
    foreach ([1, 2, 3] as $day) { // three old archives in each place
        foreach ([Storage::disk('local')->path('backups'), $copy] as $folder) {
            @mkdir($folder, 0755, true);
            file_put_contents($folder.'/backup_old_'.$day.'.zip', 'old');
            touch($folder.'/backup_old_'.$day.'.zip', now()->subDays(10 - $day)->getTimestamp());
        }
    }

    $result = app(BackupArchive::class)->create();

    expect($result['copied_to'])->toBe($copy.DIRECTORY_SEPARATOR.$result['name'])->and($result['copy_error'])->toBeNull()
        ->and(hash_file('sha256', $result['copied_to']))->toBe($result['sha256']);
    foreach ([Storage::disk('local')->path('backups'), $copy] as $folder) {
        $kept = array_map('basename', glob($folder.'/backup_*.zip'));
        expect($kept)->toHaveCount(3)->and($kept)->toContain($result['name'])->not->toContain('backup_old_1.zip');
    }

    // An unusable copy folder does not lose the backup: it is reported.
    config(['backup.copy_path' => '/definitely/not/a/writable/place/???']);
    $second = app(BackupArchive::class)->create();
    expect(is_file($second['path']))->toBeTrue()->and($second['copied_to'])->toBeNull()->and($second['copy_error'])->not->toBeNull();

    \Illuminate\Support\Facades\File::deleteDirectory($copy);
});

it('runs from the nightly job and the commands, and the commands can open it again', function () {
    baSetup();

    app()->call([new ScheduledBackup, 'handle']);
    $file = Storage::disk('local')->files('backups')[0];

    $this->artisan('backup:verify', ['file' => Storage::disk('local')->path($file)])
        ->expectsOutputToContain('The backup opens with BACKUP_PASSWORD')->expectsOutputToContain('3 uploaded files')->assertSuccessful();

    config(['backup.password' => 'a-different-password-that-is-long']);
    $this->artisan('backup:verify', ['file' => Storage::disk('local')->path($file)])->expectsOutputToContain('could not be opened')->assertFailed();
    $this->artisan('backup:verify', ['file' => '/no/such/file.zip'])->assertFailed();

    config(['backup.password' => BA_PASSWORD]);
    $this->artisan('backup:run')->expectsOutputToContain('Backup created and checked')->assertSuccessful();
    config(['backup.password' => null]);
    $this->artisan('backup:run')->expectsOutputToContain('BACKUP_PASSWORD')->assertFailed();
});

it('gives only an admin the encrypted backup, and records it', function () {
    baSetup();

    foreach ([UserRole::Student, UserRole::Coordinator, UserRole::Employer, UserRole::Supervisor] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->post(route('admin.backup'))->assertForbidden();
    }

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $response = $this->actingAs($admin)->post(route('admin.backup'))->assertOk()->assertDownload();
    expect($response->headers->get('content-disposition'))->toContain('backup_')->toContain('.zip');
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'database_backup']);

    // No password set: a clear message, no file.
    config(['backup.password' => null]);
    $this->actingAs($admin)->post(route('admin.backup'))->assertRedirect()->assertSessionHas('error', fn (string $m) => str_contains($m, 'BACKUP_PASSWORD'));
});
