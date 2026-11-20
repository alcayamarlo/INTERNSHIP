<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Account deletion requests (a student's own, or opened by an admin from the retention review) and the anonymised marker. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set when the account was anonymised (App\Services\AccountAnonymizer). Such an account can never sign in again.
            $table->timestamp('anonymised_at')->nullable()->after('archived_at');
        });

        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('student'); // student | retention
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected | cancelled
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->string('backup_name', 120)->nullable(); // the encrypted backup made right before anonymising
            $table->json('summary')->nullable();            // counts only: what was removed and what was kept
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('anonymised_at');
        });
    }
};
