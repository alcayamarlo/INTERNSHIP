<?php

namespace App\Console\Commands;

use App\Services\BackupArchive;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Make an encrypted backup of the whole database and every uploaded file now';

    public function handle(BackupArchive $backups): int
    {
        try {
            $result = $backups->create();
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup created and checked: '.$result['name']);
        $this->line('  where   : '.$result['path']);
        $this->line('  size    : '.number_format($result['size'] / 1048576, 2).' MB, '.$result['files'].' uploaded files, '.$result['tables'].' database tables');
        $this->line('  sha256  : '.$result['sha256']);
        if ($result['copied_to']) {
            $this->line('  copy    : '.$result['copied_to']);
        } elseif ($result['copy_error']) {
            $this->warn('  '.$result['copy_error']);
        } else {
            $this->warn('  No second copy: set BACKUP_COPY_PATH in .env to a folder on another drive or a synced cloud folder.');
        }

        return $result['copy_error'] ? self::FAILURE : self::SUCCESS;
    }
}
