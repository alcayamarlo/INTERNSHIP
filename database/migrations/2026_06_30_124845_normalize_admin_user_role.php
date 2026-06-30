<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'administrator')->update(['role' => 'admin']);

        if (Schema::hasTable('announcements')) {
            DB::table('announcements')->where('target_role', 'administrator')->update(['target_role' => 'admin']);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'administrator']);

        if (Schema::hasTable('announcements')) {
            DB::table('announcements')->where('target_role', 'admin')->update(['target_role' => 'administrator']);
        }
    }
};
