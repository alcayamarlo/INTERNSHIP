<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work Setup is no longer part of a posting (removed from the form, validation and every
 * display), so its column is dropped. Rolling back restores the column, empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('internships', 'work_setup')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('work_setup');
            });
        }
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->string('work_setup')->nullable()->after('allowance');
        });
    }
};
