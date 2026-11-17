<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One department (program) per coordinator and one coordinator per department.
 *
 * The link moves from programs.coordinator_id to coordinators.program_id, which is UNIQUE, so two
 * coordinators can never share a department. Deleting a department that still has a coordinator
 * is refused (restrict); deleting a coordinator frees the department.
 *
 * Existing links are kept. A coordinator who handled several programs keeps the first one (lowest
 * id); the others become "No coordinator assigned" and are logged so an admin can assign them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coordinators', 'program_id')) {
            Schema::table('coordinators', function (Blueprint $table) {
                $table->foreignId('program_id')->nullable()->after('institution_id')->unique()->constrained('programs')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('programs', 'coordinator_id')) {
            return;
        }

        $assigned = [];
        foreach (DB::table('programs')->whereNotNull('coordinator_id')->orderBy('id')->get(['id', 'code', 'coordinator_id']) as $program) {
            $coordinatorId = (int) $program->coordinator_id;
            if (isset($assigned[$coordinatorId])) {
                Log::warning('Department left without a coordinator: that coordinator already handles another department.', [
                    'program_id' => $program->id,
                    'program' => $program->code,
                    'coordinator_id' => $coordinatorId,
                ]);

                continue;
            }

            DB::table('coordinators')->where('id', $coordinatorId)->update(['program_id' => $program->id]);
            $assigned[$coordinatorId] = true;
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['coordinator_id']);
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('coordinator_id');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('coordinator_id')->nullable()->constrained('coordinators')->nullOnDelete();
        });

        foreach (DB::table('coordinators')->whereNotNull('program_id')->get(['id', 'program_id']) as $coordinator) {
            DB::table('programs')->where('id', $coordinator->program_id)->update(['coordinator_id' => $coordinator->id]);
        }

        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
        });
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropUnique(['program_id']);
        });
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropColumn('program_id');
        });
    }
};
