<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * A full, encrypted backup in one file: the database dump (database.sql), every uploaded file (files/public and
 * files/private) and a manifest, in a ZIP encrypted with AES-256 (opens with 7-Zip, WinZip or `unzip`'s AES-capable
 * builds, using BACKUP_PASSWORD). Written to storage/app/backups, optionally copied to another place
 * (BACKUP_COPY_PATH), checked by reading it back, and the oldest archives are removed beyond config('backup.keep').
 *
 * Nothing unencrypted is left behind: the temporary dump is deleted as soon as the archive is closed, and without
 * a password no backup is made at all.
 */
class BackupArchive
{
    public const MIN_PASSWORD_LENGTH = 16;

    public function __construct(private DatabaseBackup $database) {}

    /**
     * @return array{path: string, name: string, size: int, sha256: string, files: int, tables: int, copied_to: ?string, copy_error: ?string}
     *
     * @throws RuntimeException with a message that is safe to show to an admin
     */
    public function create(): array
    {
        $password = $this->password();
        if (! class_exists(ZipArchive::class) || ! defined('ZipArchive::EM_AES_256')) {
            throw new RuntimeException('This PHP has no ZIP encryption (enable extension=zip in php.ini).');
        }

        $local = Storage::disk('local');
        $local->makeDirectory('backups');
        $name = 'backup_'.now()->format('Y-m-d_His').'.zip';
        $path = $local->path('backups/'.$name);
        $dump = $local->path('backups/dump_'.bin2hex(random_bytes(6)).'.sql.tmp');

        try {
            $this->database->dumpTo($dump);
            $counts = $this->tableCounts();
            $files = $this->write($path, $dump, $password, $counts);
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception instanceof RuntimeException ? $exception : new RuntimeException('The backup could not be created: '.$exception->getMessage(), 0, $exception);
        } finally {
            // The only unencrypted copy of the data: gone as soon as the archive is written (or failed).
            if (is_file($dump)) {
                unlink($dump);
            }
        }

        $this->verify($path, $password, $files);
        [$copiedTo, $copyError] = $this->copy($path, $name);
        $this->prune($local->path('backups'));

        return [
            'path' => $path,
            'name' => $name,
            'size' => (int) filesize($path),
            'sha256' => (string) hash_file('sha256', $path),
            'files' => $files,
            'tables' => count($counts),
            'copied_to' => $copiedTo,
            'copy_error' => $copyError,
        ];
    }

    /**
     * Opens an archive with the backup password and reports what is inside. Used by `php artisan backup:verify`.
     *
     * @return array{entries: int, files: int, has_database: bool, manifest: array<string, mixed>}
     */
    public function inspect(string $path): array
    {
        $zip = new ZipArchive;
        if (! is_file($path) || $zip->open($path) !== true) {
            throw new RuntimeException('That file is not a readable backup archive.');
        }
        $zip->setPassword($this->password());
        $sql = $zip->getFromName('database.sql', 4096);
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        if ($sql === false || ! is_array($manifest)) {
            $zip->close();
            throw new RuntimeException('The archive could not be opened with BACKUP_PASSWORD (wrong password, or the file is damaged).');
        }
        $result = [
            'entries' => $zip->numFiles,
            'files' => (int) ($manifest['files'] ?? 0),
            'has_database' => $sql !== '',
            'manifest' => $manifest,
        ];
        $zip->close();

        return $result;
    }

    private function password(): string
    {
        $password = (string) config('backup.password');
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException('Backups are always encrypted. Set BACKUP_PASSWORD (at least '.self::MIN_PASSWORD_LENGTH.' characters) in .env first.');
        }

        return $password;
    }

    /** @return int how many uploaded files went in */
    private function write(string $path, string $dump, string $password, array $counts): int
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The backup file could not be created in storage/app/backups.');
        }
        $zip->setPassword($password);
        $encrypt = function (string $entry) use ($zip): void {
            $zip->setEncryptionName($entry, ZipArchive::EM_AES_256);
        };

        $zip->addFile($dump, 'database.sql');
        $encrypt('database.sql');

        $fileCount = 0;
        $bytes = 0;
        foreach ((array) config('backup.disks', []) as $disk => $folder) {
            $root = rtrim(str_replace('\\', '/', Storage::disk($disk)->path('')), '/');
            if (! is_dir($root)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getFilename() === '.gitignore') {
                    continue;
                }
                $relative = ltrim(substr(str_replace('\\', '/', $file->getPathname()), strlen($root)), '/');
                // Backups live inside the private disk's folder: never put backups (or the temporary dump) in a backup.
                if (str_starts_with($relative, 'backups/')) {
                    continue;
                }
                $zip->addFile($file->getPathname(), $folder.'/'.$relative);
                $encrypt($folder.'/'.$relative);
                $fileCount++;
                $bytes += $file->getSize();
            }
        }

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'database' => config('database.connections.'.config('database.default').'.database'),
            'files' => $fileCount,
            'file_bytes' => $bytes,
            'rows' => $counts,
            'restore' => 'Open with the backup password. Import database.sql into MySQL, copy files/public to storage/app/public and files/private to storage/app/private.',
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $encrypt('manifest.json');

        if (! $zip->close()) {
            throw new RuntimeException('The backup archive could not be written (is the disk full?).');
        }

        return $fileCount;
    }

    /** @return array<string, int> rows per table, for the manifest */
    private function tableCounts(): array
    {
        $counts = [];
        foreach (Schema::getTableListing() as $table) {
            $table = preg_replace('/^.*\./', '', $table);
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Throwable) {
                // a table that cannot be counted is simply left out of the manifest
            }
        }

        return $counts;
    }

    /** Reads the archive back: it must open with the password, hold the dump and every file, and NOT open without it. */
    private function verify(string $path, string $password, int $files): void
    {
        $fail = function (string $why) use ($path): never {
            unlink($path);

            throw new RuntimeException('The backup was discarded because it failed its check: '.$why);
        };

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $fail('it could not be reopened.');
        }
        $encrypted = $zip->getFromName('database.sql', 64) === false; // no password given
        $zip->setPassword($password);
        $dump = $zip->getFromName('database.sql', 64);
        $entries = $zip->numFiles;
        $zip->close();

        if (! $encrypted) {
            $fail('it is not encrypted.');
        }
        if ($dump === false || $dump === '') {
            $fail('the database dump is missing or empty.');
        }
        if ($entries !== $files + 2) {
            $fail("it holds {$entries} entries, expected ".($files + 2).'.');
        }
    }

    /** @return array{0: ?string, 1: ?string} where the extra copy went, or why it did not */
    private function copy(string $path, string $name): array
    {
        $folder = trim((string) config('backup.copy_path'));
        if ($folder === '') {
            return [null, null];
        }
        try {
            if (! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) {
                return [null, 'The copy folder cannot be created: '.$folder];
            }
            $target = rtrim($folder, '/\\').DIRECTORY_SEPARATOR.$name;
            if (! @copy($path, $target) || filesize($target) !== filesize($path)) {
                @unlink($target);

                return [null, 'The copy to '.$folder.' failed (is the drive connected and has free space?).'];
            }
            $this->prune($folder);

            return [$target, null];
        } catch (\Throwable $exception) {
            return [null, 'The copy failed: '.$exception->getMessage()];
        }
    }

    /** Keeps the newest config('backup.keep') archives in a folder. */
    private function prune(string $folder): void
    {
        $keep = max(1, (int) config('backup.keep', 30));
        $archives = glob(rtrim($folder, '/\\').DIRECTORY_SEPARATOR.'backup_*.zip') ?: [];
        usort($archives, fn (string $a, string $b) => filemtime($a) <=> filemtime($b));
        foreach (array_slice($archives, 0, max(0, count($archives) - $keep)) as $old) {
            @unlink($old);
        }
    }
}
