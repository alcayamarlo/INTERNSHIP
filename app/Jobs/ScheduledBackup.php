<?php

namespace App\Jobs;

use App\Services\BackupArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** The nightly backup: database and uploaded files in one encrypted archive (App\Services\BackupArchive). */
class ScheduledBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(BackupArchive $backups): void
    {
        $result = $backups->create();

        Log::info('Encrypted backup completed', ['filename' => $result['name'], 'size' => $result['size'], 'files' => $result['files'], 'copied' => $result['copied_to'] !== null]);
        if ($result['copy_error']) {
            Log::warning('The extra copy of the backup was not made', ['reason' => $result['copy_error']]);
        }
    }
}
