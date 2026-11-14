<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitations a coordinator sends to a company that has no account yet. The company completes
 * its account from an emailed link. Only a SHA-256 hash of the link token is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            // CI-YYYY-00001, built from the id right after insert, so it is unique by construction.
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name');
            $table->text('address')->nullable();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('sent');
            $table->timestamp('expires_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('employer_id')->nullable()->constrained('employers')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
    }
};
