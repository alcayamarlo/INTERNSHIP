<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->text('requirements')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('allowance', 10, 2)->nullable();
            $table->string('work_setup')->default('onsite');
            $table->string('location')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('internship_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requirement_name');
            $table->string('required_level')->default('intermediate');
            $table->timestamps();
        });

        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('cover_letter')->nullable();
            $table->unsignedTinyInteger('match_percentage')->default(0);
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('interview_at')->nullable();
            $table->text('employer_notes')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_applications');
        Schema::dropIfExists('internship_requirements');
        Schema::dropIfExists('internships');
    }
};
