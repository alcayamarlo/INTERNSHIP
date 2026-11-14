<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: the school's Final Performance Evaluation Report (form version 2) and the weekly
 * Activity Narrative Report. Old 1-5 evaluations stay as form_version 1 and are never rewritten.
 * All new date columns are nullable (MariaDB auto-update issue, see 2026_11_15_000002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // The supervisor's job title, e.g. "Solutions Architect".
            $table->string('position', 100)->nullable()->after('contact_person');
        });

        Schema::table('placements', function (Blueprint $table) {
            // The trainee's department ("Designation" on the forms), e.g. "IT Department".
            $table->string('department', 150)->nullable()->after('internship_id');
            // Due date of the final evaluation, set when the coordinator requests it.
            $table->date('rating_due_on')->nullable()->after('rating_requested_by_user_id');
        });

        Schema::table('academic_settings', function (Blueprint $table) {
            // A weekly report is due this many days after its week ends (Friday).
            $table->unsignedTinyInteger('journal_due_days')->default(3);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('form_version')->default(1)->after('evaluator_user_id');
            $table->json('work_scores')->nullable();
            $table->unsignedTinyInteger('work_total')->nullable();
            $table->json('trait_scores')->nullable();
            $table->unsignedTinyInteger('trait_total')->nullable();
            $table->string('department_assigned', 150)->nullable();
            $table->string('field_of_training', 150)->nullable();
            $table->string('supervisor_designation', 100)->nullable();
            $table->string('certified_by_name', 150)->nullable();
            $table->string('certified_by_position', 100)->nullable();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->text('accomplishments')->nullable()->change(); // no longer asked on the narrative report
            $table->text('actions_taken')->nullable()->after('challenges');
            $table->unsignedSmallInteger('week_number')->nullable()->after('week_start');
            $table->json('daily_snapshot')->nullable(); // Mon-Fri rows frozen when the supervisor certifies
        });

        // Number existing reports: week 1 is the Monday-to-Friday week containing the placement start.
        $starts = DB::table('placements')->whereNotNull('start_date')->pluck('start_date', 'id');
        DB::table('journal_entries')->orderBy('id')->get(['id', 'placement_id', 'week_start'])->each(function ($entry) use ($starts) {
            if (! isset($starts[$entry->placement_id]) || ! $entry->week_start) {
                return;
            }
            $firstMonday = CarbonImmutable::parse($starts[$entry->placement_id])->startOfWeek();
            $weekMonday = CarbonImmutable::parse($entry->week_start)->startOfWeek();
            $number = intdiv((int) $firstMonday->diffInDays($weekMonday, false), 7) + 1;
            if ($number >= 1) {
                DB::table('journal_entries')->where('id', $entry->id)->update(['week_number' => $number]);
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unique(['placement_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique(['placement_id', 'week_number']);
            $table->dropColumn(['actions_taken', 'week_number', 'daily_snapshot']);
        });
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['form_version', 'work_scores', 'work_total', 'trait_scores', 'trait_total', 'department_assigned',
                'field_of_training', 'supervisor_designation', 'certified_by_name', 'certified_by_position']);
        });
        Schema::table('academic_settings', fn (Blueprint $table) => $table->dropColumn('journal_due_days'));
        Schema::table('placements', fn (Blueprint $table) => $table->dropColumn(['department', 'rating_due_on']));
        Schema::table('employers', fn (Blueprint $table) => $table->dropColumn('position'));
    }
};
