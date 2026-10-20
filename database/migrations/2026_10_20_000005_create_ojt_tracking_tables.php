<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('required_ojt_hours')->default(300);
        });

        Schema::create('placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('internship_applications')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('required_hours');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('cleared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('clearance_remarks')->nullable();
            $table->string('certificate_number', 40)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('time_in');
            $table->time('time_out');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->decimal('hours', 5, 2);
            $table->text('task_summary');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reviewer_remarks')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['placement_id', 'work_date']);
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->string('title');
            $table->text('accomplishments');
            $table->text('learnings')->nullable();
            $table->text('challenges')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->text('supervisor_comment')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['placement_id', 'week_start']);
        });

        Schema::create('evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('scores');
            $table->decimal('average_score', 3, 2);
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('would_hire')->default(false);
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('placements');

        Schema::table('academic_settings', function (Blueprint $table): void {
            $table->dropColumn('required_ojt_hours');
        });
    }
};
