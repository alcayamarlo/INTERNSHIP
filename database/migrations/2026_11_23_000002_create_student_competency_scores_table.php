<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A student's score (0-100 %) for each competency the school rates: programming, SQL, web development,
 * problem solving, quality of work, communication, teamwork, time management, initiative and adaptability.
 * Shown on the student's profile and to companies and supervisors (App\Models\StudentCompetencyScore).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_competency_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name', 80);
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['student_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competency_scores');
    }
};
