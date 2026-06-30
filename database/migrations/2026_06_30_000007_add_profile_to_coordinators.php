<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('department');
            $table->text('office_address')->nullable()->after('profile_picture');
        });
    }

    public function down(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
            $table->dropColumn('office_address');
        });
    }
};
