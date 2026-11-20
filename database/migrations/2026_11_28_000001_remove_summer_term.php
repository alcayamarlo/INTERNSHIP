<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the Summer term. Only the two regular semesters remain, and the calendar must cover every day of the year
 * (AcademicCalendar::rangesAreContinuous), so the 2nd semester is extended to the day before the 1st semester starts:
 * with the default calendar (1st: Aug 1 - Dec 31, 2nd: Jan 1 - May 31, summer: Jun 1 - Jul 31) it now ends on Jul 31.
 * A current-term override of "summer" is cleared. No student or criteria record used summer (checked before this ran);
 * any that did would have kept the text "summer" and failed validation on their next edit, which is the point.
 *
 * down() puts the summer columns back with the default Jun 1 - Jul 31 and shortens the 2nd semester to May 31.
 */
return new class extends Migration
{
    private const COLUMNS = ['summer_enabled', 'summer_start_month', 'summer_start_day', 'summer_end_month', 'summer_end_day'];

    public function up(): void
    {
        $settings = DB::table('academic_settings')->first();
        if ($settings) {
            $dayBefore = Carbon::create(2023, (int) $settings->first_semester_start_month, (int) $settings->first_semester_start_day)->subDay();
            DB::table('academic_settings')->update([
                'second_semester_end_month' => $dayBefore->month,
                'second_semester_end_day' => $dayBefore->day,
                'current_term_override' => $settings->current_term_override === 'summer' ? null : $settings->current_term_override,
            ]);
        }

        Schema::table('academic_settings', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
            // A settings row made later starts with the 2nd semester running to Jul 31, so the two terms cover the year.
            $table->unsignedTinyInteger('second_semester_end_month')->default(7)->change();
        });
    }

    public function down(): void
    {
        Schema::table('academic_settings', function (Blueprint $table) {
            $table->boolean('summer_enabled')->default(true);
            $table->unsignedTinyInteger('summer_start_month')->default(6);
            $table->unsignedTinyInteger('summer_start_day')->default(1);
            $table->unsignedTinyInteger('summer_end_month')->default(7);
            $table->unsignedTinyInteger('summer_end_day')->default(31);
        });
        Schema::table('academic_settings', fn (Blueprint $table) => $table->unsignedTinyInteger('second_semester_end_month')->default(5)->change());
        DB::table('academic_settings')->update(['second_semester_end_month' => 5, 'second_semester_end_day' => 31]);
    }
};
