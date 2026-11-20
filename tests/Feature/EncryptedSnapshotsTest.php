<?php

use App\Models\Appraisal;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Models\Resume;
use App\Support\SensitiveColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const SNAP_HEADER = ['full_name' => 'Hazel Villaflor', 'student_id' => 'SCC-23-00024040', 'course' => 'BS IT', 'year_level' => '4th Year', 'contact_number' => '09240173281', 'address' => 'Tunghaan, Minglanilla'];
const SNAP_RESUME = ['version' => 2, 'name' => 'Hazel Villaflor', 'contact' => ['Mobile' => '09240173281', 'Email' => 'hazel@example.test', 'City address' => 'Tunghaan, Minglanilla'], 'skills' => ['PHP', 'Laravel']];

/** One of each record that freezes a copy of the student's details, made through the models. */
function snapRecords(): array
{
    $company = svCompany();
    $placement = svPlacement($company['employer']);
    $resume = Resume::create(['student_id' => $placement->student_id, 'file_path' => 'resumes/a.pdf', 'content' => SNAP_RESUME, 'generated_at' => now()]);
    $journal = JournalEntry::create(['placement_id' => $placement->id, 'week_start' => '2026-02-02', 'week_number' => 1, 'title' => 'Week 1', 'accomplishments' => 'x', 'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => JournalEntry::STATUS_SUBMITTED, 'header_snapshot' => SNAP_HEADER]);
    $appraisal = Appraisal::create(['placement_id' => $placement->id, 'period_month' => '2026-02-01', 'period_label' => 'Midterm', 'period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'due_on' => '2026-03-05', 'header_snapshot' => SNAP_HEADER]);
    $evaluation = Evaluation::create(['placement_id' => $placement->id, 'form_version' => 2, 'scores' => [], 'average_score' => 4, 'submitted_at' => now(), 'header_snapshot' => SNAP_HEADER]);

    return compact('placement', 'resume', 'journal', 'appraisal', 'evaluation');
}

const SNAP_COLUMNS = ['resumes' => 'content', 'journal_entries' => 'header_snapshot', 'appraisals' => 'header_snapshot', 'evaluations' => 'header_snapshot'];

function snapRaw(string $table, int $id): ?string
{
    return DB::table($table)->where('id', $id)->value(SNAP_COLUMNS[$table]);
}

function snapMigration(): object
{
    return require base_path('database/migrations/2026_11_27_000001_encrypt_snapshot_columns.php');
}

it('stores the copies of a student\'s details encrypted and reads them back as the same arrays', function () {
    $r = snapRecords();
    $ids = ['resumes' => $r['resume']->id, 'journal_entries' => $r['journal']->id, 'appraisals' => $r['appraisal']->id, 'evaluations' => $r['evaluation']->id];

    foreach ($ids as $table => $id) {
        $raw = snapRaw($table, $id);
        expect(SensitiveColumns::looksEncrypted($raw))->toBeTrue("{$table}.".SNAP_COLUMNS[$table].' must be encrypted')
            ->and($raw)->not->toContain('Hazel')->not->toContain('09240173281')->not->toContain('Tunghaan')
            ->and(json_decode(Crypt::decryptString($raw), true))->toBeArray(); // the stored text is the JSON
    }
    expect(Resume::find($ids['resumes'])->content)->toBe(SNAP_RESUME)
        ->and(JournalEntry::find($ids['journal_entries'])->header_snapshot)->toBe(SNAP_HEADER)
        ->and(Appraisal::find($ids['appraisals'])->header_snapshot)->toBe(SNAP_HEADER)
        ->and(Evaluation::find($ids['evaluations'])->header_snapshot)->toBe(SNAP_HEADER);

    // An empty snapshot and a null one both work.
    $empty = Resume::create(['student_id' => $r['placement']->student_id, 'file_path' => 'resumes/b.pdf', 'content' => [], 'generated_at' => now()]);
    expect(Resume::find($empty->id)->content)->toBe([]);
});

it('decrypts to JSON in down() and encrypts again in up(), and up() can run twice', function () {
    $r = snapRecords();
    $ids = ['resumes' => $r['resume']->id, 'journal_entries' => $r['journal']->id, 'appraisals' => $r['appraisal']->id, 'evaluations' => $r['evaluation']->id];
    $migration = snapMigration();

    $migration->down();
    foreach ($ids as $table => $id) {
        $raw = snapRaw($table, $id);
        expect(SensitiveColumns::looksEncrypted($raw))->toBeFalse()
            ->and(json_decode($raw, true))->toBe($table === 'resumes' ? SNAP_RESUME : SNAP_HEADER);
    }

    $migration->up();
    $once = array_map(fn ($table) => snapRaw($table, $ids[$table]), array_keys($ids));
    foreach (array_keys($ids) as $i => $table) {
        expect(SensitiveColumns::looksEncrypted($once[$i]))->toBeTrue();
    }
    expect(Resume::find($ids['resumes'])->content)->toBe(SNAP_RESUME)
        ->and(Evaluation::find($ids['evaluations'])->header_snapshot)->toBe(SNAP_HEADER);

    $migration->up(); // already encrypted: not encrypted a second time
    expect(array_map(fn ($table) => snapRaw($table, $ids[$table]), array_keys($ids)))->toBe($once);
});

it('refuses to run down() when a value cannot be decrypted or is not JSON, and changes nothing', function () {
    $r = snapRecords();
    $migration = snapMigration();
    $before = fn () => json_encode([snapRaw('resumes', $r['resume']->id), snapRaw('journal_entries', $r['journal']->id), snapRaw('appraisals', $r['appraisal']->id)]);

    $snapshot = $before();
    $foreign = base64_encode(json_encode(['iv' => base64_encode(random_bytes(16)), 'value' => base64_encode(random_bytes(24)), 'mac' => bin2hex(random_bytes(32))]));
    DB::table('appraisals')->where('id', $r['appraisal']->id)->update(['header_snapshot' => $foreign]);
    $snapshot = $before();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Cannot decrypt appraisals.header_snapshot')
        ->and($before())->toBe($snapshot);

    // Encrypted text that is not JSON would be rejected by the JSON column on the way back.
    DB::table('appraisals')->where('id', $r['appraisal']->id)->update(['header_snapshot' => Crypt::encryptString('this is not json')]);
    $snapshot = $before();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'valid JSON')
        ->and($before())->toBe($snapshot);
});

it('checks and repairs the snapshot columns with data:reencrypt', function () {
    $r = snapRecords();
    $this->artisan('data:reencrypt', ['--check' => true])->expectsOutputToContain('All personal data is encrypted')->assertSuccessful();

    DB::table('journal_entries')->where('id', $r['journal']->id)->update(['header_snapshot' => json_encode(SNAP_HEADER)]); // plain JSON, as before the migration
    $this->artisan('data:reencrypt', ['--check' => true])->expectsOutputToContain('plain text')->assertFailed();

    $this->artisan('data:reencrypt')->assertSuccessful();
    expect(SensitiveColumns::looksEncrypted(snapRaw('journal_entries', $r['journal']->id)))->toBeTrue()
        ->and(JournalEntry::find($r['journal']->id)->header_snapshot)->toBe(SNAP_HEADER);
    $this->artisan('data:reencrypt', ['--check' => true])->assertSuccessful();
});
