<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The old admin "Delete" soft-deleted users. Their email still held the
     * unique index (blocking re-registration) while login could not find them.
     * Move them into the Archived list, where admins can restore or purge them.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->update([
                'archived_at' => DB::raw('deleted_at'),
                'deleted_at' => null,
            ]);
    }

    public function down(): void
    {
        // Archived users are intentionally not soft-deleted again.
    }
};
