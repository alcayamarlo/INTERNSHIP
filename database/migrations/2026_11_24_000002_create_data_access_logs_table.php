<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who opened which student's data (App\Http\Middleware\RecordDataAccess). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('viewer_role', 20);
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('route', 100)->nullable();
            $table->string('ip_address', 64)->nullable(); // keyed hash, like system_logs
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['student_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
    }
};
