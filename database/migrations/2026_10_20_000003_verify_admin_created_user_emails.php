<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Public registration only creates student accounts, so unverified
     * admin, employer and coordinator accounts were created by an
     * administrator or seeder and never received a verification code.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereIn('role', ['admin', 'employer', 'coordinator'])
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Verification timestamps are intentionally kept.
    }
};
