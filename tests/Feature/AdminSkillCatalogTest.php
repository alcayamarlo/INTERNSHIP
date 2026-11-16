<?php

use App\Enums\SkillType;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('no longer offers employers a skills catalog', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $employer->id,
        'company_name' => 'Catalog Employer',
        'contact_person' => $employer->name,
        'verified_at' => now(),
    ]);

    expect(Route::has('employer.skills.index'))->toBeFalse();

    $this->actingAs($employer)->get('/employer/skills')->assertNotFound();
    $this->actingAs($employer)->get(route('employer.dashboard'))
        ->assertOk()
        ->assertDontSee('Skills Catalog');
});

it('normalizes skill names and stores a fixed Technical or Soft type, Technical by default', function () {
    $skill = Skill::create(['name' => '  Web   Design ']);
    $soft = Skill::create(['name' => 'Active Listening', 'type' => SkillType::Soft]);

    expect($skill->name)->toBe('Web Design')
        ->and($skill->name_normalized)->toBe('web design')
        ->and($skill->fresh()->type)->toBe(SkillType::Technical)
        ->and($skill->fresh()->type_needs_review)->toBeFalse()
        ->and($soft->fresh()->type)->toBe(SkillType::Soft);
});

it('maps old category names to a type and leaves unknown ones for review', function () {
    expect(SkillType::fromLegacyCategory('Technical'))->toBe(SkillType::Technical)
        ->and(SkillType::fromLegacyCategory('  soft   SKILLS '))->toBe(SkillType::Soft)
        ->and(SkillType::fromLegacyCategory('Interpersonal'))->toBe(SkillType::Soft)
        ->and(SkillType::fromLegacyCategory('Programming Languages'))->toBe(SkillType::Technical)
        ->and(SkillType::fromLegacyCategory('Hospitality'))->toBeNull()
        ->and(SkillType::fromLegacyCategory(null))->toBeNull();
});

it('has no Skill Categories management left, and keeps Student Skill Tags', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(Route::has('admin.skill-categories.index'))->toBeFalse()
        ->and(Schema::hasTable('skill_categories'))->toBeFalse()
        ->and(Schema::hasColumns('skills', ['type', 'type_needs_review']))->toBeTrue()
        ->and(Schema::hasColumn('skills', 'category_id'))->toBeFalse();

    $this->actingAs($admin)->get('/admin/skill-categories')->assertNotFound();
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Skill Categories')
        ->assertSee(route('admin.skill-tags.index'), false);
    $this->get(route('admin.skill-tags.index'))->assertOk();
});
