<?php

use App\Enums\UserRole;
use App\Jobs\SendNotification;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('marks admin-created accounts verified and lets admins verify stuck accounts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $institution = Institution::create(['name' => 'Coordinator College', 'address' => null]);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Coordinator',
        'email' => 'new.coordinator@example.test',
        'password' => 'Password#2026x',
        'password_confirmation' => 'Password#2026x',
        'role' => UserRole::Coordinator->value,
        'institution_id' => $institution->id,
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'new.coordinator@example.test')->first()->hasVerifiedEmail())->toBeTrue();

    $stuck = User::factory()->unverified()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $stuck->id]);

    $this->post(route('admin.users.verify-email', $stuck))->assertSessionHas('success');
    expect($stuck->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('saves the employer company profile and logo with only company fields', function () {
    Storage::fake('public');
    $user = User::factory()->create(['role' => UserRole::Employer, 'phone' => '09171234567']);
    $employer = Employer::create([
        'user_id' => $user->id,
        'company_name' => 'Old Name',
        'contact_person' => $user->name,
        'address' => '1 Main Street, Cebu City, Cebu 6000',
    ]);

    $this->actingAs($user)->postJson(route('employer.profile.update'), [
        '_method' => 'PUT',
        'company_name' => 'New Name',
        'industry' => 'Software',
        'company_size' => '11-50',
        'website' => 'https://example.test',
        'description' => 'We build things.',
        'company_logo' => UploadedFile::fake()->createWithContent(
            'logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
        ),
    ])->assertOk()->assertJsonPath('success', true);

    $employer->refresh();
    expect($employer->company_name)->toBe('New Name')
        ->and($employer->address)->toBe('1 Main Street, Cebu City, Cebu 6000')
        ->and($user->fresh()->phone)->toBe('09171234567');
    Storage::disk('public')->assertExists($employer->logo);
});

it('writes in-app notifications immediately even when the queue has no worker', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => UserRole::Coordinator]);

    app(NotificationService::class)->send($user, 'system', 'Hello', 'World');

    expect($user->appNotifications()->count())->toBe(1);
    Queue::assertPushed(SendNotification::class, fn (SendNotification $job) => $job->notificationId !== null);
});

it('regenerates a generated resume whose file was lost on redeploy', function () {
    Storage::fake('private');
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);
    $resume = Resume::create([
        'student_id' => $student->id,
        'file_path' => 'resumes/student_'.$student->id.'_lost.pdf',
        'content' => [
            'personal' => ['name' => $user->name, 'email' => $user->email],
            'competencies' => [],
            'certificates' => [],
            'portfolios' => [],
        ],
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.resume.view', $resume))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    Storage::disk('private')->assertExists($resume->file_path);
});

it('serves profile pictures without the public storage symlink', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profiles/students/avatar.png', 'png-bytes');

    $this->get('/storage/profiles/students/avatar.png')->assertOk();
    $this->get('/storage/portfolios/secret.pdf')->assertNotFound();
});
