<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->string('feedback');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'internship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_feedback');
    }
};
