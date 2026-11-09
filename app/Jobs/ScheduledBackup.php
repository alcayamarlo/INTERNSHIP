<?php

namespace App\Jobs;

use App\Services\DatabaseBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScheduledBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(DatabaseBackup $backup): void
    {
        $filename = 'backups/backup_'.now()->format('Y-m-d_His').'.sql';

        $backup->dumpTo(Storage::disk('local')->path($filename));

        $this->cleanOldBackups();

        Log::info('Database backup completed successfully', ['filename' => $filename]);
    }

    private function cleanOldBackups(): void
    {
        $disk = Storage::disk('local');
        $maxBackups = 30;

        $files = collect($disk->files('backups'))
            ->filter(fn (string $file) => str_ends_with($file, '.sql'))
            ->sortBy(fn (string $file) => $disk->lastModified($file))
            ->values();

        $files->take(max(0, $files->count() - $maxBackups))
            ->each(fn (string $file) => $disk->delete($file));
    }
}
