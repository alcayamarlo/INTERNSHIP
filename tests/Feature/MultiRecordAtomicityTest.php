<?php

use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipRequirement;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\User;
use App\Services\InternshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('rolls back an internship update and requirement replacement when creating a replacement requirement fails', function () {
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Atomic Requirements Co.',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Original title',
        'description' => 'Original description',
        'work_setup' => 'onsite',
        'status' => 'open',
    ]);
    $originalRequirement = InternshipRequirement::create([
        'internship_id' => $internship->id,
        'requirement_name' => 'Original requirement',
        'required_level' => ProficiencyLevel::Intermediate,
    ]);

    InternshipRequirement::creating(function (InternshipRequirement $requirement) {
        if ($requirement->requirement_name === 'Trigger failure') {
            throw new RuntimeException('Simulated requirement write failure.');
        }
    });

    $request = Request::create('/internships/'.$internship->id, 'PUT', [
        'requirement_names' => ['First replacement', 'Trigger failure'],
        'requirement_levels' => [
            ProficiencyLevel::Advanced->value,
            ProficiencyLevel::Advanced->value,
        ],
    ]);

    expect(fn () => app(InternshipService::class)->update(
        $internship,
        ['title' => 'Changed title'],
        $request,
    ))->toThrow(RuntimeException::class, 'Simulated requirement write failure.');

    expect($internship->fresh()->title)->toBe('Original title')
        ->and($internship->requirementsList()->count())->toBe(1)
        ->and($internship->requirementsList()->first()->is($originalRequirement))->toBeTrue();
});

it('rolls back both certificate records and removes the uploaded file when the second row fails', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);

    Portfolio::creating(function () {
        throw new RuntimeException('Simulated portfolio write failure.');
    });

    $this->actingAs($studentUser)->post(route('student.certificates.store'), [
        'title' => 'Certificate with simulated error',
        'issuer' => 'Test Issuer',
        'issue_date' => now()->subDay()->toDateString(),
        'file' => UploadedFile::fake()->create('certificate.pdf', 200, 'application/pdf'),
    ])->assertServerError();

    expect(Certificate::where('student_id', $student->id)->count())->toBe(0)
        ->and(Portfolio::where('student_id', $student->id)->count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBeEmpty();
});

it('retains a shared certificate file until both its certificate and portfolio references are deleted', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);

    $this->actingAs($studentUser)
        ->post(route('student.certificates.store'), [
            'title' => 'Shared file certificate',
            'issuer' => 'Shared Issuer',
            'issue_date' => now()->subDay()->toDateString(),
            'file' => UploadedFile::fake()->create('shared-certificate.pdf', 200, 'application/pdf'),
        ])
        ->assertRedirect(route('student.portfolio.index'));

    $certificate = Certificate::where('student_id', $student->id)->firstOrFail();
    $portfolio = Portfolio::where('student_id', $student->id)->firstOrFail();
    $sharedPath = $certificate->file_path;

    expect($portfolio->file_path)->toBe($sharedPath)
        ->and(Storage::disk('private')->exists($sharedPath))->toBeTrue();

    $this->delete(route('student.certificates.destroy', $certificate))
        ->assertRedirect(route('student.portfolio.index'));

    expect($portfolio->fresh())->not->toBeNull()
        ->and(Storage::disk('private')->exists($sharedPath))->toBeTrue();

    $this->delete(route('student.portfolio.destroyPortfolio', $portfolio))
        ->assertRedirect(route('student.portfolio.index'));

    expect(Certificate::where('student_id', $student->id)->count())->toBe(0)
        ->and(Portfolio::where('student_id', $student->id)->count())->toBe(0)
        ->and(Storage::disk('private')->exists($sharedPath))->toBeFalse();
});

it('keeps the certificate file when its generated portfolio copy is replaced', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);

    $this->actingAs($studentUser)
        ->post(route('student.certificates.store'), [
            'title' => 'Certificate with replaceable portfolio copy',
            'issuer' => 'Reference Issuer',
            'issue_date' => now()->subDay()->toDateString(),
            'file' => UploadedFile::fake()->create('reference.pdf', 200, 'application/pdf'),
        ])
        ->assertRedirect(route('student.portfolio.index'));

    $certificate = Certificate::where('student_id', $student->id)->firstOrFail();
    $portfolio = Portfolio::where('student_id', $student->id)->firstOrFail();
    $certificatePath = $certificate->file_path;

    $this->put(route('student.portfolio.update', $portfolio), [
        'title' => 'Edited portfolio copy',
        'type' => 'certificate',
        'description' => 'Updated copy.',
        'file' => UploadedFile::fake()->create('replacement.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('student.portfolio.index'));

    expect($certificate->fresh()->file_path)->toBe($certificatePath)
        ->and($portfolio->fresh()->file_path)->not->toBe($certificatePath)
        ->and(Storage::disk('private')->exists($certificatePath))->toBeTrue()
        ->and(Storage::disk('private')->exists($portfolio->fresh()->file_path))->toBeTrue();
});

it('keeps the certificate file when its portfolio copy is deleted first', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id]);

    $this->actingAs($studentUser)
        ->post(route('student.certificates.store'), [
            'title' => 'Certificate retained after portfolio deletion',
            'issuer' => 'Retention Issuer',
            'issue_date' => now()->subDay()->toDateString(),
            'file' => UploadedFile::fake()->create('retained.pdf', 200, 'application/pdf'),
        ])
        ->assertRedirect(route('student.portfolio.index'));

    $certificate = Certificate::where('student_id', $student->id)->firstOrFail();
    $portfolio = Portfolio::where('student_id', $student->id)->firstOrFail();
    $sharedPath = $certificate->file_path;

    $this->actingAs($studentUser)
        ->delete(route('student.portfolio.destroyPortfolio', $portfolio))
        ->assertRedirect(route('student.portfolio.index'));

    expect($certificate->fresh())->not->toBeNull()
        ->and(Storage::disk('private')->exists($sharedPath))->toBeTrue();
});
