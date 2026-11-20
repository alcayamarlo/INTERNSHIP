<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the JSON copies of a student's details encrypted (Laravel's `encrypted:array` cast, key: APP_KEY):
 * the resume content and the header snapshots frozen on weekly reports, monthly and final evaluations. They hold
 * the student's name, school ID, phone and address.
 *
 * up():   changes each JSON column to plain LONGTEXT (an encrypted value is not JSON, and a JSON column, or the
 *         json_valid check MariaDB puts on it, would reject it), then encrypts every existing row in place. Values
 *         that are already encrypted are skipped, so it can be run again.
 * down(): decrypts every row back to JSON text, then changes the columns back to JSON. It refuses to start if a
 *         value cannot be decrypted with the current APP_KEY or is not valid JSON, and changes nothing then.
 *
 * Rows are written with the query builder on purpose (the models would apply the cast twice); updated_at is
 * left alone. Self-contained, like 2026_11_26_000001_encrypt_sensitive_personal_data.
 */
return new class extends Migration
{
    /** table => columns (all were nullable JSON). */
    private const COLUMNS = [
        'resumes' => ['content'],
        'journal_entries' => ['header_snapshot'],
        'appraisals' => ['header_snapshot'],
        'evaluations' => ['header_snapshot'],
    ];

    public function up(): void
    {
        if ($this->changesColumns()) {
            foreach (self::COLUMNS as $table => $columns) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $column) {
                        $blueprint->longText($column)->nullable()->change();
                    }
                });
                $this->dropJsonCheck($table, $columns);
            }
        }

        foreach (self::COLUMNS as $table => $columns) {
            DB::transaction(function () use ($table, $columns) {
                $this->eachRow($table, $columns, function (object $row) use ($table, $columns) {
                    $update = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if ($value !== null && ! $this->looksEncrypted($value)) {
                            $update[$column] = Crypt::encryptString((string) $value);
                        }
                    }
                    if ($update !== []) {
                        DB::table($table)->where('id', $row->id)->update($update);
                    }
                });
            });
        }
    }

    public function down(): void
    {
        // 1. Read everything first: nothing is written unless every value can be decrypted and is valid JSON.
        $plain = [];
        foreach (self::COLUMNS as $table => $columns) {
            $this->eachRow($table, $columns, function (object $row) use ($table, $columns, &$plain) {
                foreach ($columns as $column) {
                    $value = $row->{$column};
                    if ($value === null || ! $this->looksEncrypted($value)) {
                        continue;
                    }
                    try {
                        $text = Crypt::decryptString($value);
                    } catch (Throwable) {
                        throw new RuntimeException("Cannot decrypt {$table}.{$column} (id {$row->id}). Is APP_KEY the key that encrypted it? Nothing was changed.");
                    }
                    json_decode($text);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("{$table}.{$column} (id {$row->id}) does not decrypt to valid JSON. Nothing was changed.");
                    }
                    $plain[$table][$row->id][$column] = $text;
                }
            });
        }

        // 2. Write the JSON text back (the columns are still LONGTEXT), then make them JSON columns again.
        foreach ($plain as $table => $rows) {
            DB::transaction(function () use ($table, $rows) {
                foreach ($rows as $id => $update) {
                    DB::table($table)->where('id', $id)->update($update);
                }
            });
        }

        if ($this->changesColumns()) {
            foreach (self::COLUMNS as $table => $columns) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $column) {
                        $blueprint->json($column)->nullable()->change();
                    }
                });
            }
        }
    }

    /** SQLite (tests only) has no column types to enforce, and rebuilding a table there cascade-deletes related rows. */
    private function changesColumns(): bool
    {
        return DB::getDriverName() !== 'sqlite';
    }

    /** MariaDB keeps a json_valid() check, named after the column, on a JSON column. Changing the type may leave it. */
    private function dropJsonCheck(string $table, array $columns): void
    {
        if (DB::getDriverName() !== 'mariadb' && ! str_contains(strtolower((string) DB::selectOne('select version() as v')->v), 'mariadb')) {
            return;
        }
        foreach ($columns as $column) {
            $exists = DB::selectOne(
                'select count(*) as c from information_schema.CHECK_CONSTRAINTS where CONSTRAINT_SCHEMA = database() and TABLE_NAME = ? and CONSTRAINT_NAME = ?',
                [$table, $column]
            )->c;
            if ($exists) {
                DB::statement("alter table `{$table}` drop constraint `{$column}`");
            }
        }
    }

    private function eachRow(string $table, array $columns, callable $each): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(100, function ($rows) use ($each) {
            foreach ($rows as $row) {
                $each($row);
            }
        });
    }

    private function looksEncrypted(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) < 40 || ($json = base64_decode($value, true)) === false) {
            return false;
        }
        $payload = json_decode($json, true);

        return is_array($payload) && isset($payload['iv'], $payload['value']) && (isset($payload['mac']) || isset($payload['tag']));
    }
};
