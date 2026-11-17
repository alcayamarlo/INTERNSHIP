<?php

use App\Support\IpHash;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IP addresses become keyed hashes (App\Support\IpHash, key IP_HASH_KEY).
 *
 * The columns grow to hold a 64-character hash and every address already stored is replaced by its
 * hash, in place. That cannot be undone, which is the point: the addresses are gone from the database.
 * Rows that already hold a hash are left alone, so running it twice is safe.
 */
return new class extends Migration
{
    private const TABLES = ['system_logs' => 'id', 'sessions' => 'id'];

    public function up(): void
    {
        if (blank(config('privacy.ip_hash_key'))) {
            throw new RuntimeException('IP_HASH_KEY is not set. Add it to .env (see .env.example), then run the migration again.');
        }

        foreach (array_keys(self::TABLES) as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->string('ip_address', IpHash::LENGTH)->nullable()->change());
        }

        foreach (self::TABLES as $table => $idColumn) {
            DB::table($table)
                ->whereNotNull('ip_address')
                ->whereRaw('LENGTH(ip_address) <> ?', [IpHash::LENGTH])
                ->chunkById(500, function ($rows) use ($table, $idColumn) {
                    foreach ($rows as $row) {
                        DB::table($table)->where($idColumn, $row->{$idColumn})->update(['ip_address' => IpHash::make($row->ip_address)]);
                    }
                }, $idColumn);
        }
    }

    public function down(): void
    {
        // The original addresses were replaced by hashes and cannot be restored. The wider column stays.
    }
};