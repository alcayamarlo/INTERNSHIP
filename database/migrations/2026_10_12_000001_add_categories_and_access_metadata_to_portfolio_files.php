<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('category')->default('project')->after('type');
            $table->foreignId('uploaded_by_user_id')->nullable()->after('file_path')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('category')->default('certificate')->after('file_path');
            $table->foreignId('uploaded_by_user_id')->nullable()->after('category')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('resumes', function (Blueprint $table) {
            $table->string('category')->default('resume')->after('file_path');
            $table->foreignId('uploaded_by_user_id')->nullable()->after('category')->constrained('users')->nullOnDelete();
            $table->string('verification_status')->default('available')->after('generated_at');
            $table->text('review_notes')->nullable()->after('verification_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        DB::table('portfolios')->whereIn('type', ['certificate', 'award'])->update(['category' => 'certificate']);
        DB::table('portfolios')->where('type', 'resume')->update(['category' => 'resume']);
        DB::table('portfolios')->where('type', 'transcript')->update(['category' => 'deployment_document']);
        DB::table('portfolios')->where('type', 'project')->update(['category' => 'project']);
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('uploaded_by_user_id');
            $table->dropColumn(['review_notes', 'reviewed_at', 'verification_status', 'category']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('uploaded_by_user_id');
            $table->dropColumn('category');
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('uploaded_by_user_id');
            $table->dropColumn('category');
        });
    }
};
