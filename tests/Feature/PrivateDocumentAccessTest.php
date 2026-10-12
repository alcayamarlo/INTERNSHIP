<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('serves private portfolio files only to their owning student', function () {
    Storage::fake('private');
    Storage::fake('public');

    $owner = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $ownerStudent = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $otherStudentUser = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    Student::create([
        'user_id' => $otherStudentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $path = 'portfolios/'.$ownerStudent->id.'/private.pdf';
    Storage::disk('private')->put($path, 'private evidence');
    $portfolio = Portfolio::create([
        'student_id' => $ownerStudent->id,
        'title' => 'Private Evidence',
        'type' => 'project',
        'file_path' => $path,
    ]);
    $certificatePath = 'certificates/'.$ownerStudent->id.'/certificate.pdf';
    Storage::disk('private')->put($certificatePath, 'private certificate');
    $certificate = Certificate::create([
        'student_id' => $ownerStudent->id,
        'title' => 'Private Certificate',
        'file_path' => $certificatePath,
    ]);

    $this->actingAs($owner)
        ->get(route('student.portfolio.download', $portfolio))
        ->assertOk();

    $this->actingAs($otherStudentUser)
        ->get(route('student.portfolio.download', $portfolio))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('student.certificates.download', $certificate))
        ->assertOk();

    $this->actingAs($otherStudentUser)
        ->get(route('student.certificates.download', $certificate))
        ->assertForbidden();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('previews private certificate images and PDFs inline only for the owning student', function () {
    Storage::fake('private');

    $owner = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $student = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $otherStudent = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    Student::create([
        'user_id' => $otherStudent->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $imagePath = 'certificates/'.$student->id.'/certificate.png';
    Storage::disk('private')->put(
        $imagePath,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jfXcAAAAASUVORK5CYII=', true)
    );
    $imageCertificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Image Certificate',
        'file_path' => $imagePath,
    ]);
    $pdfPath = 'certificates/'.$student->id.'/certificate.pdf';
    Storage::disk('private')->put($pdfPath, '%PDF-1.4 test document');
    $pdfCertificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'PDF Certificate',
        'file_path' => $pdfPath,
    ]);

    $this->actingAs($owner)
        ->get(route('student.certificates.preview', $imageCertificate))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Disposition', 'inline; filename="Image Certificate.png"')
        ->assertHeaderMissing('X-Frame-Options');

    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('data-preview-url="'.route('student.certificates.preview', $imageCertificate, false).'"', false)
        ->assertSee('image.src = data.previewUrl', false)
        ->assertDontSee('URL.createObjectURL');

    $this->get(route('student.certificates.preview', $pdfCertificate))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="PDF Certificate.pdf"')
        ->assertHeaderMissing('X-Frame-Options');

    $this->actingAs($otherStudent)
        ->get(route('student.certificates.preview', $imageCertificate))
        ->assertForbidden();
});

it('returns a not-found response for a missing certificate preview and exposes modal fallback', function () {
    Storage::fake('private');

    $owner = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $student = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Missing Certificate File',
        'file_path' => 'certificates/'.$student->id.'/missing.pdf',
    ]);

    $this->actingAs($owner)
        ->get(route('student.certificates.preview', $certificate))
        ->assertNotFound();

    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('Preview not available. The file could not be loaded.')
        ->assertSee(route('student.certificates.download', $certificate, false), false);
});

it('denies a different student direct access to a private resume', function () {
    Storage::fake('private');

    $owner = User::factory()->create(['role' => UserRole::Student]);
    $ownerStudent = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $otherStudent->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $path = 'resumes/'.$ownerStudent->id.'/resume.pdf';
    Storage::disk('private')->put($path, 'private resume');
    $resume = Resume::create([
        'student_id' => $ownerStudent->id,
        'file_path' => $path,
        'generated_at' => now(),
    ]);

    $this->actingAs($otherStudent)
        ->get(route('student.resume.download', $resume))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('student.resume.download', $resume))
        ->assertOk();
});

it('copies legacy files before removing public copies only when requested', function () {
    Storage::fake('private');
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $path = 'portfolios/'.$student->id.'/legacy.pdf';
    Storage::disk('public')->put($path, 'legacy evidence');
    Storage::disk('private')->put($path, 'broken evidence');
    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Legacy Evidence',
        'type' => 'project',
        'file_path' => $path,
    ]);

    Artisan::call('files:secure-existing');

    expect(Storage::disk('private')->get($path))->toBe('legacy evidence')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    Artisan::call('files:secure-existing', ['--remove-public' => true]);

    expect(Storage::disk('private')->exists($path))->toBeTrue()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('allows an employer to preview an applicant project but not download it', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Applicant Portfolio Company',
        'industry' => 'Technology',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Software Intern',
        'description' => 'Build software.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);
    $path = 'portfolios/'.$student->id.'/applicant.pdf';
    Storage::disk('private')->put($path, 'applicant portfolio');
    $portfolio = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Applicant Portfolio',
        'type' => 'project',
        'file_path' => $path,
    ]);

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.portfolios.download', [$application, $portfolio]))
        ->assertForbidden();

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.portfolios.preview', [$application, $portfolio]))
        ->assertOk();

    $otherEmployer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $otherEmployer->id,
        'company_name' => 'Other Portfolio Company',
        'contact_person' => 'Other Employer',
    ]);

    $this->actingAs($otherEmployer)
        ->get(route('employer.applicants.portfolios.preview', [$application, $portfolio]))
        ->assertForbidden();
});

it('restricts employer certificate and deployment-document access by category and status', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Category Access Company',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Intern',
        'description' => 'Internship.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);
    $verifiedPath = 'certificates/'.$student->id.'/verified.pdf';
    $pendingPath = 'certificates/'.$student->id.'/pending.pdf';
    $documentPath = 'portfolios/'.$student->id.'/consent.pdf';
    Storage::disk('private')->put($verifiedPath, 'verified certificate');
    Storage::disk('private')->put($pendingPath, 'pending certificate');
    Storage::disk('private')->put($documentPath, 'private document');
    $verified = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Verified certificate',
        'file_path' => $verifiedPath,
        'verification_status' => 'verified',
    ]);
    $pending = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Pending certificate',
        'file_path' => $pendingPath,
        'verification_status' => 'evidence_submitted',
    ]);
    $document = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Parent consent',
        'type' => 'transcript',
        'category' => 'deployment_document',
        'file_path' => $documentPath,
    ]);

    $this->actingAs($employerUser)
        ->get(route('student.certificates.preview', $verified))
        ->assertOk();
    $this->actingAs($employerUser)
        ->get(route('student.certificates.preview', $pending))
        ->assertForbidden();
    $this->actingAs($employerUser)
        ->get(route('student.portfolio.preview', $document))
        ->assertForbidden();
    $this->actingAs($employerUser)
        ->get(route('student.portfolio.download', $document))
        ->assertForbidden();
});

it('prevents students from removing protected files and resets certificate review when replaced', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $verifiedPath = 'certificates/'.$student->id.'/verified.pdf';
    Storage::disk('private')->put($verifiedPath, 'old certificate');
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Verified certificate',
        'file_path' => $verifiedPath,
        'verification_status' => 'verified',
    ]);
    $approvedPath = 'portfolios/'.$student->id.'/approved.pdf';
    Storage::disk('private')->put($approvedPath, 'approved consent');
    $document = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Parent consent',
        'type' => 'transcript',
        'category' => 'deployment_document',
        'file_path' => $approvedPath,
        'verification_status' => 'approved',
        'uploaded_by_user_id' => $studentUser->id,
    ]);

    $this->actingAs($studentUser)
        ->delete(route('student.certificates.destroy', $certificate))
        ->assertForbidden();
    $this->delete(route('student.portfolio.destroyPortfolio', $document))
        ->assertForbidden();
    $this->put(route('student.portfolio.update', $document), [
        'title' => 'Replaced consent',
        'type' => 'transcript',
        'file' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
    ])->assertForbidden();

    $response = $this->put(route('student.certificates.update', $certificate), [
        'title' => 'Updated certificate',
        'issuer' => 'Test issuer',
        'file' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
    ]);
    $response->assertRedirect(route('student.profile.edit').'#portfolio');
    expect($certificate->fresh()->verification_status)->toBe('evidence_submitted')
        ->and($certificate->fresh()->file_path)->not->toBe($verifiedPath);
});

it('resets certificate review metadata after file replacement and metadata-only edits', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $reviewer = User::factory()->create(['role' => UserRole::Admin]);
    $oldPath = 'certificates/'.$student->id.'/aws-certificate.pdf';
    Storage::disk('private')->put($oldPath, 'old certificate');
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'AWS Certified Cloud Practitioner',
        'issuer' => 'Amazon Web Services',
        'file_path' => $oldPath,
        'verification_status' => 'verified',
        'review_notes' => 'Original review',
        'reviewed_at' => now()->subDay(),
        'reviewed_by_user_id' => $reviewer->id,
    ]);

    $this->actingAs($studentUser)
        ->put(route('student.certificates.update', $certificate), [
            'title' => 'AWS Certified Cloud Practitioner',
            'issuer' => 'Amazon Web Services',
            'file' => UploadedFile::fake()->create('aws-certificate-replacement.pdf', 10, 'application/pdf'),
        ])
        ->assertRedirect(route('student.profile.edit').'#portfolio');

    expect($certificate->fresh()->verification_status)->toBe('evidence_submitted')
        ->and($certificate->fresh()->review_notes)->toBeNull()
        ->and($certificate->fresh()->reviewed_at)->toBeNull()
        ->and($certificate->fresh()->reviewed_by_user_id)->toBeNull()
        ->and($certificate->fresh()->file_path)->not->toBe($oldPath);

    $certificate->forceFill([
        'verification_status' => 'verified',
        'review_notes' => 'Review before metadata edit',
        'reviewed_at' => now()->subDay(),
        'reviewed_by_user_id' => $reviewer->id,
    ])->save();

    $this->put(route('student.certificates.update', $certificate), [
        'title' => 'AWS Certified Cloud Practitioner - Updated',
        'issuer' => 'Amazon Web Services',
    ])->assertRedirect(route('student.profile.edit').'#portfolio');

    expect($certificate->fresh()->verification_status)->toBe('evidence_submitted')
        ->and($certificate->fresh()->review_notes)->toBeNull()
        ->and($certificate->fresh()->reviewed_at)->toBeNull()
        ->and($certificate->fresh()->reviewed_by_user_id)->toBeNull();
});

it('logs administrator certificate review actions with reviewer metadata', function () {
    Storage::fake('private');
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'email_verified_at' => now(),
    ]);
    $path = 'certificates/'.$student->id.'/certificate.pdf';
    Storage::disk('private')->put($path, 'certificate');
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Certificate',
        'file_path' => $path,
        'verification_status' => 'evidence_submitted',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.portfolio-files.review', ['certificate', $certificate->id]), [
            'status' => 'verified',
        ])
        ->assertRedirect();

    expect($certificate->fresh()->verification_status)->toBe('verified')
        ->and($certificate->fresh()->reviewed_by_user_id)->toBe($admin->id)
        ->and($certificate->fresh()->reviewed_at)->not->toBeNull();
    $this->assertDatabaseHas('system_logs', [
        'user_id' => $admin->id,
        'action' => 'portfolio_file_review',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.students.files', $student))
        ->assertOk()
        ->assertSee('Files for')
        ->assertSee('Certificate');
});
