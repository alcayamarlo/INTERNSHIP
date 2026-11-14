<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per reminder sent by ojt:remind-documents, so a reminder is never sent twice.
 * kind: weekly_report | monthly_evaluation | final_evaluation
 * subject_key: the week number, the monthly evaluation id, or "final"
 * stage: the step plus before_due / overdue, e.g. "student_rating:overdue"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ojt_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('subject_key', 40);
            $table->string('stage', 60);
            $table->timestamp('sent_at')->nullable(); // nullable: see 2026_11_15_000002
            $table->timestamps();

            $table->unique(['placement_id', 'kind', 'subject_key', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ojt_reminders');
    }
};
