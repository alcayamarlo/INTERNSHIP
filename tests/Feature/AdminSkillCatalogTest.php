<?php

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipRequirement;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows employers to manage skills and keeps the legacy category value synchronized', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $employer->id,
        'company_name' => 'Catalog Employer',
        'contact_person' => $employer->name,
    ]);
    $category = SkillCategory::create(['name' => 'Technical']);

    $this->actingAs($employer)
        ->get(route('employer.skills.index'))
        ->assertOk()
        ->assertSee('Skill Catalog')
        ->assertSee('Skills Catalog');

    $this->post(route('employer.skills.store'), [
        'name' => '  Web   Design ',
        'category_id' => $category->id,
        'description' => 'Interface design fundamentals.',
    ])->assertRedirect(route('employer.skills.index'));

    $skill = Skill::firstOrFail();
    expect($skill->name)->toBe('Web Design')
        ->and($skill->name_normalized)->toBe('web design')
        ->and($skill->category)->toBe('Technical')
        ->and($skill->category_id)->toBe($category->id);

    $newCategory = SkillCategory::create(['name' => 'Creative']);
    $this->put(route('employer.skills.update', $skill), [
        'name' => 'Product Design',
        'category_id' => $newCategory->id,
        'description' => null,
    ])->assertRedirect(route('employer.skills.index'));

    expect($skill->fresh()->name)->toBe('Product Design')
        ->and($skill->fresh()->name_normalized)->toBe('product design')
        ->and($skill->fresh()->category)->toBe('Creative')
        ->and($skill->fresh()->category_id)->toBe($newCategory->id);

    $this->delete(route('employer.skills.destroy', $skill->fresh()))
        ->assertRedirect(route('employer.skills.index'));

    expect(Skill::count())->toBe(0);
});

it('enforces case and whitespace insensitive uniqueness for skills and categories', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $employer->id,
        'company_name' => 'Catalog Employer',
        'contact_person' => $employer->name,
    ]);
    $category = SkillCategory::create(['name' => 'Technical Skills']);
    $this->actingAs($employer);

    $this->post(route('employer.skills.store'), [
        'name' => 'User Experience',
        'category_id' => $category->id,
    ])->assertRedirect(route('employer.skills.index'));

    $this->from(route('employer.skills.create'))
        ->post(route('employer.skills.store'), [
            'name' => '  USER   experience ',
            'category_id' => $category->id,
        ])
        ->assertRedirect(route('employer.skills.create'))
        ->assertSessionHasErrors('name_normalized');

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

it('blocks deletion of a skill used by an internship requirement', function () {
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Example Employer',
        'contact_person' => 'Employer Contact',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Software Internship',
        'description' => 'Internship description.',
        'status' => 'open',
    ]);
    $category = SkillCategory::create(['name' => 'Technical']);
    $skill = Skill::create(['name' => 'PHP', 'category_id' => $category->id]);
    $requirement = InternshipRequirement::create([
        'internship_id' => $internship->id,
        'skill_id' => $skill->id,
        'requirement_name' => $skill->name,
        'required_level' => 'intermediate',
    ]);

    $this->actingAs($employerUser)
        ->from(route('employer.skills.index'))
        ->delete(route('employer.skills.destroy', $skill))
        ->assertRedirect(route('employer.skills.index'))
        ->assertSessionHasErrors('skill');

    expect($skill->fresh())->not->toBeNull()
        ->and($requirement->fresh()->skill_id)->toBe($skill->id);
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

it('denies students and coordinators access to the employer catalog and keeps categories admin-only', function () {
    $category = SkillCategory::create(['name' => 'Technical']);
    $skill = Skill::create(['name' => 'PHP', 'category_id' => $category->id]);

    $employer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $employer->id,
        'company_name' => 'Catalog Employer',
        'contact_person' => $employer->name,
    ]);
    $this->actingAs($employer)
        ->get(route('employer.skills.index'))
        ->assertOk();
    $this->get(route('admin.skill-categories.index'))->assertForbidden();
    $this->post(route('admin.skill-categories.store'), ['name' => 'Employer Category'])->assertForbidden();

    foreach ([UserRole::Student, UserRole::Coordinator] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('employer.skills.index'))
            ->assertForbidden();

        $this->get(route('admin.skill-categories.index'))
            ->assertForbidden();

        $this->post(route('employer.skills.store'), [
            'name' => 'Unauthorized skill',
            'category_id' => $category->id,
        ])->assertForbidden();

        $this->put(route('employer.skills.update', $skill), [
            'name' => 'Unauthorized rename',
            'category_id' => $category->id,
        ])->assertForbidden();

        $this->delete(route('employer.skills.destroy', $skill))->assertForbidden();

        $this->post(route('admin.skill-categories.store'), ['name' => 'Unauthorized category'])
            ->assertForbidden();

        $this->put(route('admin.skill-categories.update', $category), ['name' => 'Unauthorized rename'])
            ->assertForbidden();

        $this->delete(route('admin.skill-categories.destroy', $category))->assertForbidden();
    }

});
