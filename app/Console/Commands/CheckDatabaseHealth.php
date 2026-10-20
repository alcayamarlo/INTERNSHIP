<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only pre-deploy check. Run this against any database (local or
 * production, e.g. via `railway run php artisan db:health`) to catch the
 * kind of drift that broke application_status_histories and
 * academic_settings in production: an `id` primary key that lost its
 * AUTO_INCREMENT, usually from a SQL dump/restore that didn't preserve it.
 * Every insert into such a table fails with "Field 'id' doesn't have a
 * default value" until it's fixed.
 */
class CheckDatabaseHealth extends Command
{
    protected $signature = 'db:health';

    protected $description = 'Check every table for an id column that lost its AUTO_INCREMENT (read-only, safe on production)';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->info('This check only applies to MySQL; current connection is '.DB::getDriverName().'. Skipping.');

            return self::SUCCESS;
        }

        // Tables that intentionally do not use an auto-incrementing id.
        $expectedNonAutoIncrement = ['sessions', 'job_batches', 'cache', 'cache_locks'];

        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0]);

        $problems = [];

        foreach ($tables as $table) {
            if (in_array($table, $expectedNonAutoIncrement, true)) {
                continue;
            }

            $column = collect(DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'"))->first();

            if (! $column) {
                continue; // No id column at all (e.g. a pivot table) -- not a problem to flag here.
            }

            if (! str_contains(strtolower($column->Type), 'int')) {
                continue; // Non-integer id (e.g. a UUID) -- not expected to auto-increment.
            }

            if ($column->Extra !== 'auto_increment') {
                $problems[] = $table;
            }
        }

        if (empty($problems)) {
            $this->info('All tables with an integer id column have AUTO_INCREMENT set correctly.');

            return self::SUCCESS;
        }

        $this->error('These tables have an id column missing AUTO_INCREMENT -- every insert into them will fail:');
        foreach ($problems as $table) {
            $this->line('  - '.$table);
        }
        $this->newLine();
        $this->line('Fix each one with:');
        foreach ($problems as $table) {
            $this->line("  ALTER TABLE `{$table}` MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;");
        }

        return self::FAILURE;
    }
}
