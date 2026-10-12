<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->timestamp('profile_verified_at')->nullable();
            $table->foreignId('profile_verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('rendered_internship_hours')->default(0);
            $table->boolean('major_subjects_passed')->default(false);
        });

        Schema::table('academic_settings', function (Blueprint $table): void {
            $table->boolean('require_fourth_year_standing')->default(true);
            $table->boolean('require_major_subjects_passed')->default(true);
        });

        Schema::create('student_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('field', 32);
            $table->text('current_value')->nullable();
            $table->text('requested_value');
            $table->string('status', 16)->default('pending');
            $table->text('coordinator_note')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_change_requests');

        Schema::table('academic_settings', function (Blueprint $table): void {
            $table->dropColumn(['require_fourth_year_standing', 'require_major_subjects_passed']);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('profile_verified_by_user_id');
            $table->dropColumn([
                'profile_verified_at',
                'rendered_internship_hours',
                'major_subjects_passed',
            ]);
        });
    }
};
