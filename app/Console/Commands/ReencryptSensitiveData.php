<?php

namespace App\Console\Commands;

use App\Support\SensitiveColumns;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ReencryptSensitiveData extends Command
{
    protected $signature = 'data:reencrypt {--check : Only report what is stored, change nothing}';

    protected $description = 'Check that personal data is encrypted, or re-encrypt it with the current APP_KEY (after rotating the key)';

    public function handle(): int
    {
        $check = (bool) $this->option('check');
        $rows = [];
        $problems = 0;

        foreach (SensitiveColumns::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $count = ['encrypted' => 0, 'plain' => 0, 'unreadable' => 0, 'rewritten' => 0];

                DB::table($table)->select(['id', $column])->whereNotNull($column)->orderBy('id')->chunkById(200, function ($chunk) use ($table, $column, $check, &$count) {
                    DB::transaction(function () use ($chunk, $table, $column, $check, &$count) {
                        foreach ($chunk as $row) {
                            $value = $row->{$column};
                            $plain = null;
                            if (SensitiveColumns::looksEncrypted($value)) {
                                try {
                                    $plain = Crypt::decryptString($value); // current key, or an APP_PREVIOUS_KEYS one
                                    $count['encrypted']++;
                                } catch (\Throwable) {
                                    $count['unreadable']++;

                                    continue; // never overwrite what cannot be read
                                }
                            } else {
                                $count['plain']++;
                                $plain = $column === 'date_of_birth' ? $this->normalDate((string) $value) : (string) $value;
                            }
                            if (! $check) {
                                DB::table($table)->where('id', $row->id)->update([$column => Crypt::encryptString($plain)]);
                                $count['rewritten']++;
                            }
                        }
                    });
                });

                $rows[] = ["{$table}.{$column}", $count['encrypted'], $count['plain'], $count['unreadable'], $check ? '-' : $count['rewritten']];
                // Plain values are a problem when only checking; after a rewrite they are fixed. Unreadable ones always are.
                $problems += $count['unreadable'] + ($check ? $count['plain'] : 0);
            }
        }

        $this->table(['Column', 'Encrypted', 'Plain text', 'Unreadable', 'Rewritten'], $rows);

        if ($problems > 0) {
            $this->error($check
                ? 'Some values are stored in plain text or cannot be decrypted with this APP_KEY (and APP_PREVIOUS_KEYS).'
                : 'Some values cannot be decrypted with this APP_KEY (and APP_PREVIOUS_KEYS). They were left as they are.');

            return self::FAILURE;
        }
        $this->info($check ? 'All personal data is encrypted and readable with this APP_KEY.' : 'Done: every value is encrypted with the current APP_KEY.');

        return self::SUCCESS;
    }

    private function normalDate(string $value): string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }
}
