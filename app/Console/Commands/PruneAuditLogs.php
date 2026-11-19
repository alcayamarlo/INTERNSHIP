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

        // Data access records (who opened which student's data).
        $accessCutoff = now()->subDays((int) config('security.access_log.keep_days', 365));
        $accessDeleted = 0;
        do {
            $batch = \App\Models\DataAccessLog::query()->where('created_at', '<', $accessCutoff)->limit(1000)->delete();
            $accessDeleted += $batch;
        } while ($batch > 0);
        $this->info("Removed {$accessDeleted} data access entr".($accessDeleted === 1 ? 'y' : 'ies').' older than '.$accessCutoff->toDateString().'.');

        return self::SUCCESS;
    }
}
