<?php

use App\Support\CatalogName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $skills = DB::table('skills')->orderBy('id')->get(['id', 'name', 'category']);
        $normalizedNames = [];
        foreach ($skills as $skill) {
            $normalized = CatalogName::normalized($skill->name);
            if (isset($normalizedNames[$normalized])) {
                throw new \RuntimeException(sprintf(
                    'Cannot normalize duplicate skill names for IDs %d and %d (%s). Resolve the duplicate before migrating.',
                    $normalizedNames[$normalized],
                    $skill->id,
                    $normalized
                ));
            }
            $normalizedNames[$normalized] = $skill->id;
        }

        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_normalized')->unique();
            $table->timestamps();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->string('name_normalized')->nullable();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('skill_categories')
                ->restrictOnDelete();
        });

        $categoryIds = [];
        foreach ($skills as $skill) {
            $categoryName = CatalogName::display($skill->category ?: 'skill');
            $normalizedCategory = CatalogName::normalized($categoryName);

            if (! isset($categoryIds[$normalizedCategory])) {
                $categoryIds[$normalizedCategory] = DB::table('skill_categories')->insertGetId([
                    'name' => $categoryName,
                    'name_normalized' => $normalizedCategory,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('skills')->where('id', $skill->id)->update([
                'name' => CatalogName::display($skill->name),
                'name_normalized' => CatalogName::normalized($skill->name),
                'category' => DB::table('skill_categories')->where('id', $categoryIds[$normalizedCategory])->value('name'),
                'category_id' => $categoryIds[$normalizedCategory],
            ]);
        }

        Schema::table('skills', function (Blueprint $table) {
            $table->string('name_normalized')->nullable(false)->change();
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->unique('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['name_normalized']);
            $table->dropColumn(['category_id', 'name_normalized']);
        });

        Schema::dropIfExists('skill_categories');
    }
};
