<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates a PDF report for coordinators without crashing', function () {
    Storage::fake('public');

    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $user = User::factory()->create([
        'role' => UserRole::Coordinator,
    ]);

    Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    $this->actingAs($user)
        ->post(route('coordinator.reports.generate'), [
            'type' => 'placement',
            'format' => 'pdf',
        ])
        ->assertOk();

    $this->assertDatabaseHas('reports', [
        'generated_by' => $user->id,
        'type' => 'placement',
        'format' => 'pdf',
    ]);
});
