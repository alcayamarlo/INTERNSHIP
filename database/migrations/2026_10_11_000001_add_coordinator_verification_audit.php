<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['student_competencies', 'certificates', 'portfolios'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('reviewed_at')
                    ->constrained('coordinators')
                    ->nullOnDelete();
            });
        }

        Schema::create('verification_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 32);
            $table->unsignedBigInteger('item_id');
            $table->string('item');
            $table->string('action', 32);
            $table->text('note')->nullable();
            $table->foreignId('coordinator_id')
                ->nullable()
                ->constrained('coordinators')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_activities');

        foreach (['student_competencies', 'certificates', 'portfolios'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('reviewed_by');
            });
        }
    }
};
