<?php

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

it('normalizes skill names and keeps the legacy category value synchronized', function () {
    $category = SkillCategory::create(['name' => 'Technical']);
    $skill = Skill::create(['name' => '  Web   Design ', 'category_id' => $category->id]);

    expect($skill->name)->toBe('Web Design')
        ->and($skill->name_normalized)->toBe('web design')
        ->and($skill->category)->toBe('Technical')
        ->and($skill->category_id)->toBe($category->id);
});

it('enforces case and whitespace insensitive uniqueness for categories', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);
    $this->post(route('admin.skill-categories.store'), ['name' => 'Soft Skills'])
        ->assertRedirect(route('admin.skill-categories.index'));

    $this->from(route('admin.skill-categories.create'))
        ->post(route('admin.skill-categories.store'), ['name' => '  soft   SKILLS '])
        ->assertRedirect(route('admin.skill-categories.create'))
        ->assertSessionHasErrors('name_normalized');
});

it('updates the legacy category string when a category is renamed', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = SkillCategory::create(['name' => 'Technical']);
    $skill = Skill::create(['name' => 'PHP', 'category_id' => $category->id]);

    $this->actingAs($admin)
        ->get(route('admin.skill-categories.index'))
        ->assertOk()
        ->assertSee('Skill Categories');

    $this->get(route('admin.skill-categories.edit', $category))
        ->assertOk()
        ->assertSee('Technical');

    $this->put(route('admin.skill-categories.update', $category), ['name' => 'Engineering'])
        ->assertRedirect(route('admin.skill-categories.index'));

    expect($skill->fresh()->category)->toBe('Engineering')
        ->and($skill->fresh()->category_id)->toBe($category->id)
        ->and($category->fresh()->name_normalized)->toBe('engineering');
});

it('blocks deletion of a category while skills use it and allows deletion after they are removed', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = SkillCategory::create(['name' => 'Technical']);
    $skill = Skill::create(['name' => 'PHP', 'category_id' => $category->id]);

    $this->actingAs($admin)
        ->from(route('admin.skill-categories.index'))
        ->delete(route('admin.skill-categories.destroy', $category))
        ->assertRedirect(route('admin.skill-categories.index'))
        ->assertSessionHasErrors('category');

    $skill->delete();

    $this->delete(route('admin.skill-categories.destroy', $category))
        ->assertRedirect(route('admin.skill-categories.index'));

    expect(SkillCategory::count())->toBe(0);
});

it('keeps skill categories admin-only', function () {
    $category = SkillCategory::create(['name' => 'Technical']);

    foreach ([UserRole::Employer, UserRole::Student, UserRole::Coordinator] as $role) {
        $user = User::factory()->create(['role' => $role]);
        if ($role === UserRole::Employer) {
            Employer::create(['user_id' => $user->id, 'company_name' => 'Catalog Employer', 'contact_person' => $user->name]);
        }

        $this->actingAs($user)->get(route('admin.skill-categories.index'))->assertForbidden();
        $this->post(route('admin.skill-categories.store'), ['name' => 'Unauthorized category'])->assertForbidden();
        $this->put(route('admin.skill-categories.update', $category), ['name' => 'Unauthorized rename'])->assertForbidden();
        $this->delete(route('admin.skill-categories.destroy', $category))->assertForbidden();
    }
});
