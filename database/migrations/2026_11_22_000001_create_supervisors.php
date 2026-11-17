<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supervisors: company staff the HR account creates to run the OJT day to day. One supervisor can have
 * any number of interns. The intern points at the supervisor's user account (placements.supervisor_user_id);
 * placements.supervisor_name stays as the printed name and as the fallback for older records.
 *
 * Date columns are nullable on purpose: on this MariaDB a NOT NULL timestamp would get an implicit
 * "ON UPDATE CURRENT_TIMESTAMP" (see 2026_11_15_000002_stop_timestamp_auto_update).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('position', 100)->nullable(); // job title, printed on the school forms
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('placements', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor_user_id')->nullable()->after('employer_id')->index();
            // SQLite (the test database) rebuilds the whole table to add a foreign key, which turns the
            // partial unique index on student_id into a full one. The code ignores a missing supervisor
            // (Placement::activeSupervisor), so the foreign key is only added on MySQL.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->foreign('supervisor_user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['supervisor_user_id']);
            }
            $table->dropIndex(['supervisor_user_id']);
            $table->dropColumn('supervisor_user_id');
        });
        Schema::dropIfExists('supervisors');
    }
};
