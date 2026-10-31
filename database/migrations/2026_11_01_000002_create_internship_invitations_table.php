<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('message', 500)->nullable();
            $table->string('status', 20)->default('pending');
            // "student:internship" while pending, NULL otherwise: the unique index allows only one
            // pending invitation per student and internship (MySQL has no partial unique index).
            $table->string('pending_key', 50)->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('application_id')->nullable()->constrained('internship_applications')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'internship_id']);
            $table->index(['employer_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        // "Allow companies to find my profile". Only counts once the student has given privacy consent.
        Schema::table('students', function (Blueprint $table): void {
            $table->boolean('discoverable')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('discoverable');
        });
        Schema::dropIfExists('internship_invitations');
    }
};
