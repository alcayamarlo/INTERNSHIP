<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function profilePicturePng(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
    );
}

it('uploads and displays student profile pictures on the public disk', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => profilePicturePng('student.png')])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['profile_picture_url']);

    $path = $student->fresh()->profile_picture;
    Storage::disk('public')->assertExists($path);
    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('/storage/'.$path, false);
});

it('uploads and displays coordinator profile pictures on the public disk', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => Institution::create(['name' => 'Picture Test Institution'])->id,
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
            'profile_picture' => profilePicturePng('coordinator.png'),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['profile_picture_url']);

    $path = $coordinator->fresh()->profile_picture;
    Storage::disk('public')->assertExists($path);
    $this->get(route('coordinator.profile.edit'))
        ->assertOk()
        ->assertSee('/storage/'.$path, false);
});

it('uploads and displays employer logos on the public disk', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $user->id,
        'company_name' => 'Picture Test Employer',
        'contact_person' => $user->name,
    ]);

    $this->actingAs($user)
        ->postJson(route('employer.profile.update'), [
            '_method' => 'PUT',
            'company_name' => $employer->company_name,
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
            'company_logo' => profilePicturePng('employer.png'),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['logo_url']);

    $path = $employer->fresh()->logo;
    Storage::disk('public')->assertExists($path);
    $this->get(route('employer.profile.edit'))
        ->assertOk()
        ->assertSee('/storage/'.$path, false);
});

it('allows administrators to upload and display their profile picture', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($user)
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);

    $this->post(route('admin.profile.update-picture'), ['avatar' => profilePicturePng('admin.png')])
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHasNoErrors();

    $path = $user->fresh()->avatar;
    expect($path)->toStartWith('profiles/admins/');
    Storage::disk('public')->assertExists($path);
    $this->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('/storage/'.$path, false);
});
