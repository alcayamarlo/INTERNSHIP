<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'logs:prune';

    protected $description = 'Delete audit and security log entries older than the retention period (1 year)';

    public function handle(): int
    {
        $cutoff = now()->subDays(SystemLog::RETENTION_DAYS);
        $deleted = 0;

        // In batches so a large table never locks for long.
        do {
            $batch = SystemLog::query()->where('created_at', '<', $cutoff)->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Removed {$deleted} log entr".($deleted === 1 ? 'y' : 'ies').' older than '.$cutoff->toDateString().'.');

        return self::SUCCESS;
    }
}
