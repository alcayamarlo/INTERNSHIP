<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('saves a coordinator profile and photo asynchronously', function () {
    Storage::fake('public');
    $institution = Institution::create(['name' => 'Coordinator AJAX Institution']);
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Career Services',
        'office_address' => 'Main Campus',
    ]);

    $this->actingAs($user)
        ->postJson(route('coordinator.profile.update'), [
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '09123456789',
            'department' => 'Career Services',
            'position' => 'Coordinator',
            'office_address' => 'Main Campus',
            'profile_picture' => UploadedFile::fake()->createWithContent(
                'coordinator.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASsJTYQAAAAASUVORK5CYII=')
            ),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['profile_picture_url']);

    expect($coordinator->fresh()->profile_picture)->not->toBeNull();
    Storage::disk('public')->assertExists($coordinator->fresh()->profile_picture);

    $this->get(route('coordinator.profile.edit'))
        ->assertOk()
        ->assertSee('data-ajax-form', false)
        ->assertSee('data-coordinator-feedback', false);
});

it('saves an employer profile and logo asynchronously', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $user->id,
        'company_name' => 'AJAX Employer',
        'contact_person' => $user->name,
    ]);

    $this->actingAs($user)
        ->postJson(route('employer.profile.update'), [
            '_method' => 'PUT',
            'company_name' => 'Updated AJAX Employer',
            'industry' => 'Technology',
            'company_size' => '1-10',
            'contact_person' => $user->name,
            'position' => 'Hiring Manager',
            'email' => $user->email,
            'phone' => '09123456789',
            'street' => '1 Main Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'zip_code' => '6000',
            'company_logo' => UploadedFile::fake()->createWithContent(
                'company.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
            ),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['logo_url']);

    expect($employer->fresh()->company_name)->toBe('Updated AJAX Employer')
        ->and($employer->fresh()->logo)->not->toBeNull();
    Storage::disk('public')->assertExists($employer->fresh()->logo);

    $this->get(route('employer.profile.edit'))
        ->assertOk()
        ->assertSee('data-ajax-form', false)
        ->assertSee('data-employer-feedback', false);
});
