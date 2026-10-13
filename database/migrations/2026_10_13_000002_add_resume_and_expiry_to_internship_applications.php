<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('status');
        });

        Schema::table('internship_applications', function (Blueprint $table) {
            $table->foreignId('resume_id')
                ->nullable()
                ->after('student_id')
                ->constrained('resumes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('internship_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resume_id');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
