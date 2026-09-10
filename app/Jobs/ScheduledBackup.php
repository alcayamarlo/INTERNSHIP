<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScheduledBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        $filename = 'backups/backup_'.now()->format('Y-m-d_His').'.sql';

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg(storage_path('app/'.$filename))
        );

        if ($password) {
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg(storage_path('app/'.$filename))
            );
        }

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("Database backup failed", ['output' => $output]);
            throw new \RuntimeException('Database backup failed: '.implode("\n", $output));
        }

        $this->cleanOldBackups();

        Log::info("Database backup completed successfully", ['filename' => $filename]);
    }

    private function cleanOldBackups(): void
    {
        $files = Storage::disk('local')->files('backups');
        $maxBackups = 30;

        if (count($files) > $maxBackups) {
            $filesToDelete = array_slice($files, 0, count($files) - $maxBackups);
            foreach ($filesToDelete as $file) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}
