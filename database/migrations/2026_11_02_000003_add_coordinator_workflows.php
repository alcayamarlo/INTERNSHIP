<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coordinator workflows: eligibility decisions (with history), placement validation, company MOA
 * tracking, postings a coordinator marks unsuitable, and the coordinator's second review of weekly
 * reports and evaluations. All additive; existing rows and statuses are not changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 10);
            $table->text('reason')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'id']);
        });

        Schema::table('placements', function (Blueprint $table): void {
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('validation_remarks')->nullable();
        });

        Schema::table('employers', function (Blueprint $table): void {
            $table->date('moa_signed_on')->nullable();
            $table->date('moa_expires_on')->nullable();
            $table->timestamp('moa_reminded_at')->nullable();
            $table->foreignId('moa_updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('internship_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coordinator_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500);
            $table->timestamps();
            $table->unique(['internship_id', 'coordinator_id']);
        });

        foreach (['journal_entries', 'evaluations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('coordinator_review_status', 10)->nullable();
                $table->text('coordinator_comment')->nullable();
                $table->timestamp('coordinator_reviewed_at')->nullable();
                $table->foreignId('coordinator_reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['journal_entries', 'evaluations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('coordinator_reviewed_by_user_id');
                $table->dropColumn(['coordinator_review_status', 'coordinator_comment', 'coordinator_reviewed_at']);
            });
        }
        Schema::dropIfExists('internship_flags');
        Schema::table('employers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('moa_updated_by_user_id');
            $table->dropColumn(['moa_signed_on', 'moa_expires_on', 'moa_reminded_at']);
        });
        Schema::table('placements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('validated_by_user_id');
            $table->dropColumn(['validated_at', 'validation_remarks']);
        });
        Schema::dropIfExists('eligibility_decisions');
    }
};
