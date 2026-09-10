<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_requirements', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('required_level');
        });
    }

    public function down(): void
    {
        Schema::table('internship_requirements', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });
    }
};
