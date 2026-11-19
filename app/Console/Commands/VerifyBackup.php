<?php

namespace App\Console\Commands;

use App\Services\BackupArchive;
use Illuminate\Console\Command;

class VerifyBackup extends Command
{
    protected $signature = 'backup:verify {file : Path to a backup_*.zip}';

    protected $description = 'Open a backup with BACKUP_PASSWORD and show what it contains (changes nothing)';

    public function handle(BackupArchive $backups): int
    {
        try {
            $result = $backups->inspect((string) $this->argument('file'));
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->info('The backup opens with BACKUP_PASSWORD.');
        $this->line('  made on : '.($manifest['created_at'] ?? '?'));
        $this->line('  database: '.($manifest['database'] ?? '?').' ('.count($manifest['rows'] ?? []).' tables, '.array_sum($manifest['rows'] ?? []).' rows)');
        $this->line('  files   : '.$result['files'].' uploaded files');
        foreach (['users', 'students', 'employers', 'placements', 'student_competencies'] as $table) {
            if (isset($manifest['rows'][$table])) {
                $this->line(sprintf('  %-22s %d rows', $table, $manifest['rows'][$table]));
            }
        }

        return self::SUCCESS;
    }
}
