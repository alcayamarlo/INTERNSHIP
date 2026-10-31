<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Databases that ran an early draft of add_bio_data_profile got a student_skills table pointing at
 * the employer skills catalog and no skill_tags table. Bring them in line; fresh installs skip this.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('skill_tags')) {
            Schema::create('skill_tags', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 60);
                $table->string('name_normalized', 60)->unique();
                $table->timestamps();
            });

            $bioData = require __DIR__.'/2026_10_31_000001_add_bio_data_profile.php';
            $now = now();
            DB::table('skill_tags')->insert(array_map(fn (string $name) => [
                'name' => $name,
                'name_normalized' => mb_strtolower($name),
                'created_at' => $now,
                'updated_at' => $now,
            ], $bioData::STARTER_SKILLS));
        }

        if (! Schema::hasColumn('student_skills', 'skill_id')) {
            return;
        }

        if (DB::table('student_skills')->exists()) {
            throw new RuntimeException('student_skills still uses skill_id and has rows; move them to skill_tags by hand before migrating.');
        }

        Schema::drop('student_skills');
        Schema::create('student_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_tag_id')->constrained('skill_tags')->restrictOnDelete();
            $table->string('level', 20)->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'skill_tag_id']);
        });
    }

    public function down(): void
    {
        // Nothing to undo: the tables now match add_bio_data_profile, whose down() drops them.
    }
};
