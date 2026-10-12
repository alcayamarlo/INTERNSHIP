<?php

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes the student academic sidebar shortcut while preserving the profile tab and admin access', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $student->id]);

    $this->actingAs($student)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertDontSee(route('admin.academics.index'), false)
        ->assertDontSee(route('student.profile.edit').'#academic', false)
        ->assertDontSee('<span>Academic</span>', false)
        ->assertDontSee('href="'.route('student.portfolio.index').'"', false)
        ->assertDontSee('<span>Portfolio</span>', false)
        ->assertSee('id="tab-academic"', false);

    $this->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(route('student.profile.edit').'#portfolio', false)
        ->assertSee('Manage Portfolio');

    $this->get(route('admin.academics.index'))->assertForbidden();

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee(route('admin.academics.index'), false)
        ->assertSee('Academic settings');
});
