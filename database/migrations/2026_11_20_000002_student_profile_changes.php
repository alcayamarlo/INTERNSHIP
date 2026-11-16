<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Student profile changes (existing data is converted, not lost):
 *  - Height in feet and inches instead of centimetres (converted from height_cm).
 *  - "Career objectives" becomes "About me" in Personal Info (column renamed, text kept).
 *  - Separate Senior High School entry; every "Year graduated" is a school-year range such as
 *    2022-2023 (a stored year 2019 becomes 2018-2019).
 *  - Preferred internship field, work setup and location are removed.
 *  - Character references are optional: postings can no longer require one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedTinyInteger('height_feet')->nullable()->after('religion');
            $table->unsignedTinyInteger('height_inches')->nullable()->after('height_feet');
            $table->string('senior_high_school')->nullable()->after('high_school_year_graduated');
            $table->string('senior_high_year_graduated', 9)->nullable()->after('senior_high_school');
        });

        DB::table('students')->whereNotNull('height_cm')->orderBy('id')->each(function ($student) {
            $inches = (int) round(((float) $student->height_cm) / 2.54);
            DB::table('students')->where('id', $student->id)->update([
                'height_feet' => intdiv($inches, 12),
                'height_inches' => $inches % 12,
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('elementary_year_graduated', 9)->nullable()->change();
            $table->string('high_school_year_graduated', 9)->nullable()->change();
        });

        foreach (['elementary_year_graduated', 'high_school_year_graduated'] as $column) {
            DB::table('students')->whereNotNull($column)->orderBy('id')->each(function ($student) use ($column) {
                $value = trim((string) $student->{$column});
                if (preg_match('/^\d{4}$/', $value)) {
                    DB::table('students')->where('id', $student->id)->update([$column => ((int) $value - 1).'-'.$value]);
                }
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('career_objectives', 'about_me');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['height_cm', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location']);
        });

        if (Schema::hasColumn('internships', 'requires_character_reference')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('requires_character_reference');
            });
        }
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->boolean('requires_character_reference')->default(false);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->string('preferred_internship_field')->nullable();
            $table->string('preferred_work_setup')->nullable();
            $table->string('preferred_location')->nullable();
        });

        DB::table('students')->whereNotNull('height_feet')->orderBy('id')->each(function ($student) {
            DB::table('students')->where('id', $student->id)->update([
                'height_cm' => round(((int) $student->height_feet * 12 + (int) $student->height_inches) * 2.54, 1),
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('about_me', 'career_objectives');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['height_feet', 'height_inches', 'senior_high_school', 'senior_high_year_graduated']);
        });
    }
};
