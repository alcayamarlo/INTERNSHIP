<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores students' personal data encrypted (Laravel's encrypted cast, key: APP_KEY).
 *
 * up():   widens each column to TEXT (a ciphertext is longer than the value), then encrypts every existing row in
 *         place. Values that are already encrypted are skipped, so it can be run again after a failure.
 * down(): decrypts every row back to plain text, then narrows the columns to their original types. It refuses to
 *         start if any value cannot be decrypted with the current APP_KEY (or would not fit the original width), and
 *         changes nothing in that case.
 *
 * Rows are written with the query builder on purpose: going through the models would apply the casts twice.
 * `updated_at` is left alone. The models carry the casts (Student, User, CharacterReference); this class is
 * self-contained so that it keeps working if they change.
 */
return new class extends Migration
{
    /**
     * table => column => [original type, original length (strings only), nullable].
     * date_of_birth was a DATE; everything else a string or text.
     */
    private const COLUMNS = [
        'students' => [
            'date_of_birth' => ['date', null, true],
            'place_of_birth' => ['string', 150, true],
            'address' => ['text', null, true],
            'city' => ['string', 255, true],
            'province' => ['string', 255, true],
            'zip_code' => ['string', 255, true],
            'provincial_address' => ['string', 255, true],
            'emergency_contact_name' => ['string', 150, true],
            'emergency_contact_relationship' => ['string', 60, true],
            'emergency_contact_address' => ['string', 255, true],
            'emergency_contact_phone' => ['string', 30, true],
            'religion' => ['string', 100, true],
            'civil_status' => ['string', 30, true],
        ],
        'users' => [
            'phone' => ['string', 255, true],
        ],
        'character_references' => [
            'contact_number' => ['string', 30, false],
        ],
    ];

    /**
     * SQLite (used only by the tests) does not enforce column lengths, so it needs no resizing. It must not get it:
     * SQLite rebuilds the whole table for a column change, and with foreign keys on that deletes the rows that depend on it.
     */
    private function resizesColumns(): bool
    {
        return DB::getDriverName() !== 'sqlite';
    }

    public function up(): void
    {
        foreach ($this->resizesColumns() ? self::COLUMNS : [] as $table => $columns) {
            $widen = array_filter($columns, fn (array $spec) => $spec[0] !== 'text');
            if ($widen !== []) {
                Schema::table($table, function (Blueprint $blueprint) use ($widen) {
                    foreach ($widen as $column => [, , $nullable]) {
                        $blueprint->text($column)->nullable($nullable)->change();
                    }
                });
            }
        }

        foreach (self::COLUMNS as $table => $columns) {
            DB::transaction(function () use ($table, $columns) {
                $this->eachRow($table, array_keys($columns), function (object $row, array $names) use ($table) {
                    $update = [];
                    foreach ($names as $column) {
                        $value = $row->{$column};
                        if ($value === null || $this->looksEncrypted($value)) {
                            continue;
                        }
                        $update[$column] = Crypt::encryptString($column === 'date_of_birth' ? $this->normalDate((string) $value) : (string) $value);
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
        // 1. Read everything first: nothing is written unless every value can be decrypted and will fit.
        $plain = [];
        foreach (self::COLUMNS as $table => $columns) {
            $this->eachRow($table, array_keys($columns), function (object $row, array $names) use ($table, $columns, &$plain) {
                foreach ($names as $column) {
                    $value = $row->{$column};
                    if ($value === null || ! $this->looksEncrypted($value)) {
                        continue; // already plain text
                    }
                    try {
                        $text = Crypt::decryptString($value);
                    } catch (Throwable) {
                        throw new RuntimeException("Cannot decrypt {$table}.{$column} (id {$row->id}). Is APP_KEY the key that encrypted it? Nothing was changed.");
                    }
                    [, $length] = $columns[$column];
                    if ($length !== null && mb_strlen($text) > $length) {
                        throw new RuntimeException("{$table}.{$column} (id {$row->id}) is longer than the original {$length} characters. Nothing was changed.");
                    }
                    $plain[$table][$row->id][$column] = $text;
                }
            });
        }

        // 2. Write the plain values back (still in the wide columns), then narrow the columns.
        foreach ($plain as $table => $rows) {
            DB::transaction(function () use ($table, $rows) {
                foreach ($rows as $id => $update) {
                    DB::table($table)->where('id', $id)->update($update);
                }
            });
        }

        foreach ($this->resizesColumns() ? self::COLUMNS : [] as $table => $columns) {
            $narrow = array_filter($columns, fn (array $spec) => $spec[0] !== 'text');
            if ($narrow !== []) {
                Schema::table($table, function (Blueprint $blueprint) use ($narrow) {
                    foreach ($narrow as $column => [$type, $length, $nullable]) {
                        $definition = $type === 'date' ? $blueprint->date($column) : $blueprint->string($column, $length);
                        $definition->nullable($nullable)->change();
                    }
                });
            }
        }
    }

    /** Calls $each(row, columns) for every row, 200 at a time. */
    private function eachRow(string $table, array $columns, callable $each): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(200, function ($rows) use ($columns, $each) {
            foreach ($rows as $row) {
                $each($row, $columns);
            }
        });
    }

    /** The shape of an encrypted payload: base64 of JSON with iv, value and mac (or tag). */
    private function looksEncrypted(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) < 40 || ($json = base64_decode($value, true)) === false) {
            return false;
        }
        $payload = json_decode($json, true);

        return is_array($payload) && isset($payload['iv'], $payload['value']) && (isset($payload['mac']) || isset($payload['tag']));
    }

    private function normalDate(string $value): string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return $value; // unreadable: keep exactly what was there
        }
    }
};
