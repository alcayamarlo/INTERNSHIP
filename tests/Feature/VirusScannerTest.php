<?php

use App\Enums\UserRole;
use App\Models\Message;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\VirusScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** The harmless standard antivirus test marker, built in two pieces so this file is not flagged itself. */
function vsEicar(): string
{
    return 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS'.'-TEST-FILE!$H+H*';
}

/** Turns scanning on, with the PHP stand-in for clamscan (tests/Fixtures/fake-clamscan.php). */
function vsEnable(): void
{
    config(['security.clamav.path' => PHP_BINARY, 'security.clamav.options' => [base_path('tests/Fixtures/fake-clamscan.php')], 'security.clamav.timeout' => 30]);
}

function vsPdf(string $extra = ''): UploadedFile
{
    return UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\n% a small test document\n{$extra}\n%%EOF\n");
}

it('is off until CLAMAV_PATH is set, and then files are not scanned at all', function () {
    Storage::fake('local');
    expect(app(VirusScanner::class)->enabled())->toBeFalse();

    // Even a file carrying the test marker is stored while the scanner is off: nothing changes before ClamAV is installed.
    $path = app(FileUploadService::class)->upload(vsPdf(vsEicar()), 'portfolios/1', 'doc', 'local');
    Storage::disk('local')->assertExists($path);
});

it('lets a clean file through and stores it', function () {
    Storage::fake('local');
    vsEnable();
    expect(app(VirusScanner::class)->enabled())->toBeTrue();

    $path = app(FileUploadService::class)->upload(vsPdf('a clean resume'), 'portfolios/1', 'doc', 'local');

    Storage::disk('local')->assertExists($path);
});

it('refuses an infected file before anything is stored, and records it', function () {
    Storage::fake('local');
    vsEnable();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $this->actingAs($user);

    expect(fn () => app(FileUploadService::class)->upload(vsPdf(vsEicar()), 'portfolios/1', 'doc', 'local'))
        ->toThrow(ValidationException::class, 'may contain a virus');
    expect(Storage::disk('local')->allFiles())->toBe([]);

    $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'upload_blocked']);
    $log = \App\Models\SystemLog::where('action', 'upload_blocked')->latest('id')->first();
    expect($log->details['reason'])->toBe('virus_found')->and($log->details['signature'])->toBe('Eicar-Test-Signature');
});

it('fails closed when the scanner is missing, crashes, or times out', function () {
    Storage::fake('local');
    $upload = fn (string $extra = '') => app(FileUploadService::class)->upload(vsPdf($extra), 'portfolios/1', 'doc', 'local');

    // Configured but the program is not there.
    config(['security.clamav.path' => base_path('no-such-clamscan.exe')]);
    expect(fn () => $upload())->toThrow(ValidationException::class, 'virus scanner is not available');

    // The program runs and reports an error (exit code 2).
    vsEnable();
    expect(fn () => $upload('SCANNER-CRASH'))->toThrow(ValidationException::class, 'could not check this file');

    // It does not finish in time.
    config(['security.clamav.timeout' => 1]);
    expect(fn () => $upload('SCANNER-SLOW'))->toThrow(ValidationException::class, 'did not finish');

    expect(Storage::disk('local')->allFiles())->toBe([]); // never stored
    expect(\App\Models\SystemLog::where('action', 'upload_blocked')->pluck('details')->map(fn ($d) => $d['reason'])->unique()->sort()->values()->all())
        ->toBe(['scanner_error', 'scanner_unavailable']);
});

it('shows the refusal next to the field the file came in on', function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
    vsEnable();
    $student = User::factory()->create(['role' => UserRole::Student]);
    \App\Models\Student::create(['user_id' => $student->id, 'privacy_consent' => true, 'privacy_consent_at' => now()]);

    // The portfolio form's own field name is used for the message.
    $response = $this->actingAs($student)->from(route('student.portfolio.index'))->post(route('student.portfolio.store'), [
        'title' => 'My project', 'type' => 'project', 'file' => vsPdf(vsEicar()),
    ]);

    $response->assertSessionHasErrors('file');
    expect(session('errors')->first('file'))->toContain('may contain a virus')
        ->and(\App\Models\Portfolio::count())->toBe(0);
});

it('also scans photos sent in messages', function () {
    Storage::fake('local');
    vsEnable();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $recipient = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($admin)->post(route('messages.store', $recipient), ['attachment' => UploadedFile::fake()->createWithContent('pic.png', $png.vsEicar()), 'submission_id' => (string) Str::uuid()], ['Accept' => 'application/json'])
        ->assertStatus(422);
    expect(Message::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);

    $this->post(route('messages.store', $recipient), ['attachment' => UploadedFile::fake()->createWithContent('pic.png', $png), 'submission_id' => (string) Str::uuid()], ['Accept' => 'application/json'])
        ->assertOk();
    expect(Message::count())->toBe(1);
});
