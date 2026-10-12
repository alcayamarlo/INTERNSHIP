<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->unsignedSmallInteger('required_internship_hours');
            $table->boolean('required_hours_confirmed')->default(false);
            $table->foreignId('coordinator_id')->nullable()->constrained('coordinators')->nullOnDelete();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['program_id', 'name']);
        });

        Schema::create('academic_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('student_id_pattern', 255)->default('^SCC-\\d{2}-\\d{8}$');
            $table->unsignedTinyInteger('academic_year_start_month')->default(8);
            $table->boolean('summer_enabled')->default(true);
            $table->string('current_term_override', 10)->nullable();
            $table->unsignedTinyInteger('first_semester_start_month')->default(8);
            $table->unsignedTinyInteger('first_semester_start_day')->default(1);
            $table->unsignedTinyInteger('first_semester_end_month')->default(12);
            $table->unsignedTinyInteger('first_semester_end_day')->default(31);
            $table->unsignedTinyInteger('second_semester_start_month')->default(1);
            $table->unsignedTinyInteger('second_semester_start_day')->default(1);
            $table->unsignedTinyInteger('second_semester_end_month')->default(5);
            $table->unsignedTinyInteger('second_semester_end_day')->default(31);
            $table->unsignedTinyInteger('summer_start_month')->default(6);
            $table->unsignedTinyInteger('summer_start_day')->default(1);
            $table->unsignedTinyInteger('summer_end_month')->default(7);
            $table->unsignedTinyInteger('summer_end_day')->default(31);
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
            $table->foreignId('major_id')->nullable()->after('program_id')->constrained('majors')->nullOnDelete();
            $table->string('section', 50)->nullable();
            $table->string('school_year', 9)->nullable();
            $table->string('semester', 10)->nullable();
            $table->string('enrollment_status', 20)->nullable();
            $table->decimal('gwa', 6, 2)->nullable();
            $table->unique('student_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_id_number']);
            $table->dropConstrainedForeignId('major_id');
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['section', 'school_year', 'semester', 'enrollment_status', 'gwa']);
        });

        Schema::dropIfExists('academic_settings');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('programs');
    }
};
