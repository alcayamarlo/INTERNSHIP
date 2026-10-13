<?php

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores a valid student profile picture on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('student.profile.edit'))
        ->post(route('student.profile.update-picture'), [
            'profile_picture' => UploadedFile::fake()->createWithContent(
                'profile.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
            ),
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
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');

    $this->actingAs($user)
        ->from(route('student.profile.edit'))
        ->post(route('student.profile.update-picture'), [
            'profile_picture' => UploadedFile::fake()->createWithContent(
                'profile.png',
                $png.str_repeat("\0", (2048 * 1024) + 1 - strlen($png))
            ),
        ])
        ->assertRedirect(route('student.profile.edit'))
        ->assertSessionHasErrors('profile_picture');
});

it('returns the new profile picture URL for an asynchronous upload', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('student.profile.update-picture'), [
            'profile_picture' => UploadedFile::fake()->createWithContent(
                'profile.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
            ),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Profile picture updated successfully.')
        ->assertJsonStructure(['profile_picture_url']);

    Storage::disk('public')->assertExists($student->fresh()->profile_picture);
});
