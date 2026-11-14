<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * On MySQL/MariaDB servers with explicit_defaults_for_timestamp = 0 (XAMPP's MariaDB 10.4 default), the
 * first NOT NULL `timestamp` column of a table silently becomes
 * `DEFAULT current_timestamp() ON UPDATE current_timestamp()`. Any update to the row then overwrites it:
 * opening a company invitation link expired it at once, invitation reminders reset student invitation
 * expiry, and coordinator reviews overwrote the evaluation submission date.
 *
 * A nullable timestamp never gets that implicit behaviour, so declare these columns nullable. The app
 * always fills them, and existing values are kept.
 *
 * Rule for new migrations on this server: declare date columns ->nullable() (or use dateTime()).
 */
return new class extends Migration
{
    /** @var array<string, string> table => column */
    private const COLUMNS = [
        'company_invitations' => 'expires_at',
        'internship_invitations' => 'expires_at',
        'evaluations' => 'submitted_at',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->timestamp($column)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->timestamp($column)->nullable(false)->change();
            });
        }
    }
};
