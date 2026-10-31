<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional 2x2 picture, kept on the private disk. A plain nullable column (not a document row) so it
 * never counts as a required document or toward profile completion. Existing rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('id_photo_path')->nullable()->after('profile_picture');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('id_photo_path');
        });
    }
};
