<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly Evaluation (the school's Performance Appraisal Report): one per placement per month.
 * Order: student rates -> supervisor rates and comments -> student comments -> coordinator (adviser) rates.
 *
 * Every timestamp is nullable on purpose: on MariaDB with explicit_defaults_for_timestamp = 0 a NOT NULL
 * timestamp silently auto-updates on every change (see 2026_11_15_000002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('period_month'); // first day of the evaluated month
            $table->string('period_label', 40); // stored, so a later change to the month names never alters a signed form
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_on')->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('self_scores')->nullable();
            $table->unsignedTinyInteger('self_total')->nullable();
            $table->timestamp('self_submitted_at')->nullable();

            $table->json('supervisor_scores')->nullable();
            $table->unsignedTinyInteger('supervisor_total')->nullable();
            $table->text('supervisor_comment')->nullable();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_submitted_at')->nullable();

            $table->text('student_comment')->nullable();
            $table->timestamp('student_comment_submitted_at')->nullable();

            $table->json('adviser_scores')->nullable();
            $table->unsignedTinyInteger('adviser_total')->nullable();
            $table->foreignId('adviser_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adviser_submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['placement_id', 'period_month']);
        });

        Schema::table('academic_settings', function (Blueprint $table) {
            // Month number => evaluation period name (e.g. 2 => "Midterm"). Months without a name use the month name.
            $table->json('evaluation_period_labels')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academic_settings', function (Blueprint $table) {
            $table->dropColumn('evaluation_period_labels');
        });
        Schema::dropIfExists('appraisals');
    }
};
