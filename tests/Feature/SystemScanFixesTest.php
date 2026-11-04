<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\User;
use App\Support\CsvCell;
use App\Support\DownloadName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function scanStudent(): array
{
    $user = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);

    return [$user, Student::create(['user_id' => $user->id, 'profile_completion' => 0])];
}

it('downloads and previews files whose title contains a slash, quote, or backslash', function () {
    Storage::fake('private');
    Storage::fake('public');
    [$user, $student] = scanStudent();

    Storage::disk('private')->put('portfolios/'.$student->id.'/waiver.jpg', 'image');
    $portfolio = Portfolio::create(['student_id' => $student->id, 'title' => 'Parent or guardian consent / waiver', 'type' => 'project', 'file_path' => 'portfolios/'.$student->id.'/waiver.jpg']);
    Storage::disk('private')->put('certificates/'.$student->id.'/cert.pdf', 'pdf');
    $certificate = Certificate::create(['student_id' => $student->id, 'title' => 'NC II "Cookery" \\ TESDA', 'file_path' => 'certificates/'.$student->id.'/cert.pdf']);

    $download = $this->actingAs($user)->get(route('student.portfolio.download', $portfolio))->assertOk();
    expect($download->headers->get('Content-Disposition'))->toContain('Parent or guardian consent - waiver.jpg');

    $this->actingAs($user)->get(route('student.portfolio.preview', $portfolio))->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename="Parent or guardian consent - waiver.jpg"');

    $certificateDownload = $this->actingAs($user)->get(route('student.certificates.download', $certificate))->assertOk();
    expect($certificateDownload->headers->get('Content-Disposition'))->toContain('NC II -Cookery- - TESDA.pdf');
});

it('builds safe download names', function () {
    expect(DownloadName::make('a/b\\c"d', 'x/file.PDF'))->toBe('a-b-c-d.pdf')
        ->and(DownloadName::make('  ///  ', 'x/file.png'))->toBe('file.png')
        ->and(DownloadName::make(null, 'x/file'))->toBe('file')
        ->and(DownloadName::inline('Ñiño / cert', 'a.pdf'))->toContain('filename="Nino - cert.pdf"')->toContain("filename*=utf-8''");
});

it('neutralises spreadsheet formulas in exported cells but keeps numbers', function () {
    expect(CsvCell::row(['=HYPERLINK("x")', '+1+1', '-cmd', '@SUM(A1)', 'Normal', '-5', 12, null, '']))
        ->toBe(["'=HYPERLINK(\"x\")", "'+1+1", "'-cmd", "'@SUM(A1)", 'Normal', '-5', 12, null, '']);
});

it('hides a moderated posting from students who did not apply, but not from applicants', function () {
    [$applicantUser, $applicant] = scanStudent();
    [$otherUser] = scanStudent();
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Hidden Co', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Reported Role', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open', 'hidden_at' => now(), 'hidden_reason' => 'Scam or fake opportunity']);
    InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $applicant->id, 'status' => \App\Enums\ApplicationStatus::Applied, 'match_percentage' => 50]);

    $this->actingAs($otherUser)->get(route('student.internships.show', $internship))->assertNotFound();
    $this->actingAs($applicantUser)->get(route('student.internships.show', $internship))->assertOk()->assertSee('Reported Role');
});
