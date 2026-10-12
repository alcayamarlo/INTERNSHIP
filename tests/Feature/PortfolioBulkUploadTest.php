<?php

use App\Enums\UserRole;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('allows a student to upload multiple portfolio files in one request', function () {
    Storage::fake('private');
    Storage::fake('public');

    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => 'student-bulk@example.com',
    ]);

    Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('student.portfolio.bulk-store'), [
            'items' => [
                [
                    'title' => 'Project One',
                    'type' => 'project',
                    'description' => 'First project artifact.',
                    'file' => UploadedFile::fake()->create('one.pdf', 200, 'application/pdf'),
                ],
                [
                    'title' => 'Project Two',
                    'type' => 'project',
                    'description' => 'Second project artifact.',
                    'file' => UploadedFile::fake()->create('two.pdf', 200, 'application/pdf'),
                ],
            ],
        ])
        ->assertRedirect(route('student.profile.edit').'#portfolio')
        ->assertSessionHas('success', '2 portfolio items uploaded successfully!');

    expect(Portfolio::where('student_id', $user->student->id)->count())->toBe(2)
        ->and(Storage::disk('private')->allFiles())->toHaveCount(2)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});
