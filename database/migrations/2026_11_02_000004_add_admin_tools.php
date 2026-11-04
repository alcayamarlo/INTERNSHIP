<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin tools: account suspension, institution verification, content reports and moderation
 * (hiding postings and messages), and an index for filtering the audit log. Additive only; existing
 * institutions are marked verified so nothing that works today stops working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('suspended_until')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('institutions')->whereNull('verified_at')->update(['verified_at' => now()]);

        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 20);
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('status', 15)->default('open');
            $table->string('resolution', 15)->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['reportable_type', 'reportable_id']);
        });

        Schema::table('internships', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable();
            $table->string('hidden_reason', 500)->nullable();
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable();
        });

        Schema::table('system_logs', function (Blueprint $table): void {
            $table->index('created_at');
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table): void {
            $table->dropIndex(['action', 'created_at']);
            $table->dropIndex(['created_at']);
        });
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn('hidden_at'));
        Schema::table('internships', fn (Blueprint $table) => $table->dropColumn(['hidden_at', 'hidden_reason']));
        Schema::dropIfExists('content_reports');
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by_user_id');
            $table->dropColumn('verified_at');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('suspended_by_user_id');
            $table->dropColumn(['suspended_at', 'suspended_until', 'suspension_reason']);
        });
    }
};
