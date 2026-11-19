<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Two-factor sign-in by email code (App\Services\TwoFactor). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optional for coordinators, HR and supervisors; admins always use it.
            $table->boolean('two_factor_enabled')->default(false)->after('locked_until');
            // JSON list of keyed hashes of the unused recovery codes (the codes themselves are shown once).
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
            // The emailed code: keyed hash, expiry and wrong tries.
            $table->string('two_factor_code_hash', 64)->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('two_factor_code_expires_at')->nullable()->after('two_factor_code_hash');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_code_expires_at');
            // Set by `php artisan user:reset-2fa`: the next password sign-in skips the code once.
            $table->boolean('two_factor_skip_once')->default(false)->after('two_factor_attempts');
            // Seeded presentation accounts: their addresses are not real, so codes are never emailed to them.
            $table->boolean('demo_account')->default(false)->after('two_factor_skip_once');
        });

        Schema::create('two_factor_trusted_browsers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('label', 120)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_trusted_browsers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled', 'two_factor_recovery_codes', 'two_factor_code_hash',
                'two_factor_code_expires_at', 'two_factor_attempts', 'two_factor_skip_once', 'demo_account',
            ]);
        });
    }
};
