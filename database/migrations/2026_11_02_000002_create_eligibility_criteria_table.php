<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internship criteria per program and term. Rows are never edited: each save adds a new version,
 * so earlier versions stay as history. Programs without criteria keep the global academic settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('school_year', 9);
            $table->string('term', 10);
            $table->unsignedInteger('version');
            $table->boolean('require_fourth_year')->default(false);
            $table->boolean('require_major_subjects_passed')->default(false);
            // Philippine GWA: lower is better, so this is the highest GWA allowed (e.g. 2.50).
            $table->decimal('max_gwa', 3, 2)->nullable();
            $table->unsignedInteger('required_hours')->nullable();
            $table->json('required_document_ids')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'school_year', 'term', 'version'], 'eligibility_criteria_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_criteria');
    }
};
