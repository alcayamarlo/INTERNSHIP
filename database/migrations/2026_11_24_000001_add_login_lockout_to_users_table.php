<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Account lockout after repeated wrong passwords (App\Services\LoginLockout). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('failed_logins')->default(0)->after('last_login_at');
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_logins');
            $table->timestamp('locked_until')->nullable()->after('last_failed_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_logins', 'last_failed_login_at', 'locked_until']);
        });
    }
};
