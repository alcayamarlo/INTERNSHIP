<?php

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('Needs the GD extension.');
    }
});

/** A real (decodable) PNG; photos are now decoded and re-encoded with GD. */
function validProfilePng(string $name = 'profile.png'): UploadedFile
{
    $image = imagecreatetruecolor(8, 8);
    ob_start();
    imagepng($image);

    return UploadedFile::fake()->createWithContent($name, (string) ob_get_clean());
}

it('stores a valid student profile picture on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('student.profile.edit'))
        ->post(route('student.profile.update-picture'), [
            'profile_picture' => validProfilePng(),
        ])
        ->assertRedirect(route('student.profile.edit'))
        ->assertSessionHasNoErrors();

    $path = $student->fresh()->profile_picture;

    expect($path)->toStartWith('profiles/students/');
    Storage::disk('public')->assertExists($path);
});

it('rejects profile pictures larger than the configured upload limit', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('student.profile.edit'))
        ->post(route('student.profile.update-picture'), [
            'profile_picture' => UploadedFile::fake()->create('profile.png', ImageProcessor::MAX_KB + 1, 'image/png'),
        ])
        ->assertRedirect(route('student.profile.edit'))
        ->assertSessionHasErrors(['profile_picture' => ImageProcessor::SIZE_MESSAGE]);
});

it('returns the new profile picture URL for an asynchronous upload', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('student.profile.update-picture'), [
            'profile_picture' => validProfilePng(),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Profile picture updated successfully.')
        ->assertJsonStructure(['profile_picture_url']);

    Storage::disk('public')->assertExists($student->fresh()->profile_picture);
});
