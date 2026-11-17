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
            'industry' => 'Information',
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
        ->and($employer->fresh()->company_size)->toBe('1-10')
        ->and($employer->fresh()->logo)->not->toBeNull();

    // Company size is stored on the record, so it is still selected in a fresh session (after logout).
    $this->flushSession();
    $this->actingAs($user->fresh())
        ->get(route('employer.profile.edit'))
        ->assertOk()
        ->assertSee('value="1-10" selected', false);
    Storage::disk('public')->assertExists($employer->fresh()->logo);

    $this->get(route('employer.profile.edit'))
        ->assertOk()
        ->assertSee('data-ajax-form', false)
        ->assertSee('data-employer-feedback', false);
});

function crossRolePng(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII='));
}

it('keeps the employer logo when the profile is saved without a new one', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $user->id, 'company_name' => 'Keep Logo Co', 'contact_person' => $user->name]);
    $profile = ['_method' => 'PUT', 'company_name' => 'Keep Logo Co', 'industry' => 'Information', 'contact_person' => $user->name,
        'email' => $user->email, 'phone' => '09123456789', 'street' => '1 Main Street', 'city' => 'Cebu City', 'province' => 'Cebu', 'zip_code' => '6000'];

    $this->actingAs($user)->postJson(route('employer.profile.update-logo'), ['company_logo' => crossRolePng('logo.png')])->assertOk();
    $logo = $employer->fresh()->logo;
    Storage::disk('public')->assertExists($logo);

    // Saving the rest of the profile afterwards must not delete the logo file.
    $this->postJson(route('employer.profile.update'), $profile)->assertOk();
    expect($employer->fresh()->logo)->toBe($logo);
    Storage::disk('public')->assertExists($logo);
    $this->get(route('employer.profile.edit'))->assertOk()->assertSee('/storage/'.$logo, false);
});

it('uploads a coordinator picture on its own, even with an unfinished profile, and keeps it on later saves', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Coordinator, 'phone' => null]);
    $coordinator = Coordinator::create(['user_id' => $user->id, 'department' => 'Internship Office']);

    // No position, office address or phone yet: the picture still saves.
    $this->actingAs($user)
        ->postJson(route('coordinator.profile.update-picture'), ['profile_picture' => crossRolePng('me.png')])
        ->assertOk()
        ->assertJsonPath('success', true);
    $picture = $coordinator->fresh()->profile_picture;
    Storage::disk('public')->assertExists($picture);

    // The placeholder phone format (+63 900 000 0000) is accepted, and saving keeps the picture.
    $this->postJson(route('coordinator.profile.update'), ['_method' => 'PUT', 'name' => $user->name, 'email' => $user->email,
        'phone' => '+63 900 000 0002', 'department' => 'Internship Office', 'position' => 'Coordinator', 'office_address' => 'Main Campus'])
        ->assertOk();
    expect($coordinator->fresh()->profile_picture)->toBe($picture);
    Storage::disk('public')->assertExists($picture);
});

it('shows initials instead of a broken image when a saved picture file is missing', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create(['user_id' => $user->id, 'company_name' => 'Gone Logo Co', 'contact_person' => $user->name, 'logo' => 'logos/employers/missing.jpg']);

    expect(\App\Support\Avatar::url('logos/employers/missing.jpg'))->toBeNull()
        ->and(\App\Support\Avatar::for($user)['url'])->toBeNull();
    $this->actingAs($user)->get(route('employer.profile.edit'))->assertOk()->assertDontSee('logos/employers/missing.jpg', false);
});
