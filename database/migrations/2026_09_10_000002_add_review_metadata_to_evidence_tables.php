<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_competencies', function (Blueprint $table) {
            $table->text('review_notes')->nullable()->after('verification_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->text('review_notes')->nullable()->after('verification_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('verification_status')->default('evidence_submitted')->after('file_path');
            $table->text('review_notes')->nullable()->after('verification_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('student_competencies', function (Blueprint $table) {
            $table->dropColumn(['review_notes', 'reviewed_at']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['review_notes', 'reviewed_at']);
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'review_notes', 'reviewed_at']);
        });
    }
};
