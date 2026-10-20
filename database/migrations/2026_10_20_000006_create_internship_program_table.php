<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Programs an internship is open to. An internship with no rows is open to every program.
     */
    public function up(): void
    {
        Schema::create('internship_program', function (Blueprint $table): void {
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->primary(['internship_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_program');
    }
};
