<?php

use App\Enums\SkillType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Skill Categories (admin-editable) are replaced by a fixed type: Technical or Soft.
 * Every skill is mapped from its old category name; one that cannot be mapped becomes
 * Technical with type_needs_review = true (and is logged), so no skill is lost.
 * Rolling back recreates the Technical and Soft categories from the type.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('skills', 'type')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->string('type', 20)->default(SkillType::Technical->value)->after('name_normalized');
                $table->boolean('type_needs_review')->default(false)->after('type');
            });
        }

        $categoryNames = Schema::hasTable('skill_categories')
            ? DB::table('skill_categories')->pluck('name', 'id')
            : collect();
        $hasCategoryId = Schema::hasColumn('skills', 'category_id');
        $hasCategory = Schema::hasColumn('skills', 'category');

        if ($hasCategoryId || $hasCategory) {
            $columns = array_values(array_filter(['id', 'name', $hasCategory ? 'category' : null, $hasCategoryId ? 'category_id' : null]));
            foreach (DB::table('skills')->select($columns)->orderBy('id')->get() as $skill) {
                $oldCategory = ($hasCategoryId ? $categoryNames[$skill->category_id] ?? null : null) ?? ($skill->category ?? null);
                $type = SkillType::fromLegacyCategory($oldCategory);
                if ($type === null) {
                    Log::warning('Skill type set to Technical; please review.', ['skill_id' => $skill->id, 'skill' => $skill->name, 'old_category' => $oldCategory]);
                }

                DB::table('skills')->where('id', $skill->id)->update([
                    'type' => ($type ?? SkillType::Technical)->value,
                    'type_needs_review' => $type === null,
                ]);
            }
        }

        if ($hasCategoryId) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        }
        if ($hasCategory) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        Schema::dropIfExists('skill_categories');
    }

    public function down(): void
    {
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_normalized')->unique();
            $table->timestamps();
        });
        $ids = [];
        foreach ([SkillType::Technical->value => 'Technical', SkillType::Soft->value => 'Soft'] as $type => $name) {
            $ids[$type] = DB::table('skill_categories')->insertGetId([
                'name' => $name, 'name_normalized' => strtolower($name), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('skills', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('skill_categories')->restrictOnDelete();
        });
        foreach ($ids as $type => $id) {
            DB::table('skills')->where('type', $type)->update([
                'category_id' => $id,
                'category' => DB::table('skill_categories')->where('id', $id)->value('name'),
            ]);
        }

        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn(['type', 'type_needs_review']);
        });
    }
};
