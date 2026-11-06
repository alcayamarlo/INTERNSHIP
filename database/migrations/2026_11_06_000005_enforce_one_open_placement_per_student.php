<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One open OJT per student.
 *
 * placements only had UNIQUE(application_id), so when two companies accepted the same student
 * (two different applications) the student got two placements and appeared twice in the
 * coordinator/admin placement lists.
 *
 * "Open" means pending_validation or active and not soft-deleted. Completed/cleared placements
 * are history and are not limited. Placements have no school-year column, so the rule is
 * "one ongoing OJT at a time", which is what a term-based key would approximate anyway.
 *
 * MySQL/MariaDB have no partial indexes, so a stored generated column holds student_id only
 * while the placement is open (NULL otherwise; NULLs never collide in a UNIQUE index).
 * SQLite/PostgreSQL use a partial unique index instead.
 */
return new class extends Migration
{
    private const INDEX = 'placements_one_open_per_student';

    public function up(): void
    {
        // Never resolve duplicates silently here: a person must pick which OJT to keep.
        $duplicates = DB::table('placements')
            ->select('student_id')
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending_validation', 'active'])
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('student_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add the one-open-placement rule: these student IDs (students.id) have more than one open placement: '
                .$duplicates->implode(', ')
                .'. Back up the database, then run database/sql/dedupe_open_placements.sql (read its notes first), and migrate again.'
            );
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('placements', function (Blueprint $table): void {
                $table->unsignedBigInteger('open_student_id')
                    ->nullable()
                    ->storedAs("IF(deleted_at IS NULL AND status IN ('pending_validation','active'), student_id, NULL)")
                    ->after('student_id');
                $table->unique('open_student_id', self::INDEX);
            });

            return;
        }

        DB::statement('CREATE UNIQUE INDEX '.self::INDEX." ON placements (student_id) WHERE deleted_at IS NULL AND status IN ('pending_validation', 'active')");
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('placements', function (Blueprint $table): void {
                $table->dropUnique(self::INDEX);
                $table->dropColumn('open_student_id');
            });

            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
    }
};
