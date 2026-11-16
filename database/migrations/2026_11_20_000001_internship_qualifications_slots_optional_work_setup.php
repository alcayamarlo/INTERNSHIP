<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Posting form changes:
 *  - "General Requirements" is now "Qualifications": the column is renamed (data kept).
 *  - New optional "Number of slots".
 *  - Work Setup is no longer asked: the column becomes optional with no default. Existing values
 *    are kept in the database but no longer shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->renameColumn('requirements', 'qualifications');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->unsignedSmallInteger('slots')->nullable()->after('qualifications');
            $table->string('work_setup')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn('slots');
            $table->string('work_setup')->nullable(false)->default('onsite')->change();
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->renameColumn('qualifications', 'requirements');
        });
    }
};
