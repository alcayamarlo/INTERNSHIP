<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\CompanyVisibleProfile;
use App\Services\ImageProcessor;
use App\Services\ResumeBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('Needs the GD extension (php -d extension=gd artisan test).');
    }
    Storage::fake('public');
    Storage::fake('private');
});

function photoStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz', 'phone' => '09171234567']);

    return Student::create(['user_id' => $user->id, 'privacy_consent' => true, 'privacy_consent_at' => now(), ...$attributes]);
}

/** A 200x100 image: left half $left, right half $right. */
function photoBytes(string $format = 'jpeg', array $left = [220, 20, 40], array $right = [20, 40, 220], int $width = 200, int $height = 100): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, ...$left));
    imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, ...$right));
    ob_start();
    match ($format) {
        'png' => imagepng($image),
        'webp' => imagewebp($image),
        default => imagejpeg($image, null, 95),
    };

    return (string) ob_get_clean();
}

/** The same JPEG with an EXIF block: Orientation 6 (phone held upright) and a GPS latitude. */
function photoWithExif(): string
{
    $jpeg = photoBytes();
    $tiff = 'II'.pack('v', 42).pack('V', 8)
        .pack('v', 2)
        .pack('vvV', 0x0112, 3, 1).pack('vv', 6, 0)
        .pack('vvVV', 0x8825, 4, 1, 38)
        .pack('V', 0)
        .pack('v', 1)
        .pack('vvV', 0x0001, 2, 2)."N\0\0\0"
        .pack('V', 0);
    $segment = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($jpeg, 2);
}

function photoUpload(string $bytes, string $name = 'photo.jpg'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes);
}

/** RGB at a point of a stored or embedded JPEG. */
function pixelAt(string $jpeg, int $x, int $y): array
{
    $image = imagecreatefromstring($jpeg);
    $rgb = imagecolorat($image, $x, $y);

    return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
}

function isReddish(array $rgb): bool
{
    return $rgb[0] > 150 && $rgb[2] < 100;
}

function isBluish(array $rgb): bool
{
    return $rgb[2] > 150 && $rgb[0] < 100;
}

it('keeps the profile photo file when the Personal, Contact, and Academic tabs are saved', function () {
    $student = photoStudent();
    $this->actingAs($student->user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload(photoBytes())])
        ->assertOk();
    $path = $student->fresh()->profile_picture;

    $payload = [
        ...bioDataDefaults(),
        'first_name' => 'Ana', 'last_name' => 'Cruz',
        'email' => $student->user->email, 'phone' => '09171234567',
        'address' => '45 Osmeña Blvd', 'city' => 'Cebu City', 'province' => 'Cebu', 'zip_code' => '6000',
        'expected_graduation' => '2027-05-01',
    ];
    foreach (['personal', 'contact', 'academic'] as $section) {
        $this->putJson(route('student.profile.update'), [...$payload, 'profile_section' => $section])->assertOk();
        Storage::disk('public')->assertExists($path);
        expect($student->fresh()->profile_picture)->toBe($path);
    }
});

it('accepts JPG, PNG, and WebP profile photos and saves a 400x400 JPEG with a random name', function (string $format) {
    $student = photoStudent();

    $response = $this->actingAs($student->user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload(photoBytes($format), "me.{$format}")])
        ->assertOk();

    $path = $student->fresh()->profile_picture;
    $saved = Storage::disk('public')->get($path);
    expect($path)->toMatch('#^profiles/students/[A-Za-z0-9]{40}\.jpg$#')
        ->and(getimagesizefromstring($saved))->toMatchArray([0 => 400, 1 => 400, 'mime' => 'image/jpeg'])
        ->and($response->json('profile_picture_url'))->toContain('?v=');
})->with(['jpeg', 'png', 'webp']);

it('accepts JPG, PNG, and WebP 2x2 pictures and saves a 600x600 JPEG on the private disk', function (string $format) {
    $student = photoStudent();

    $this->actingAs($student->user)
        ->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes($format), "id.{$format}")])
        ->assertOk()
        ->assertJsonPath('message', 'Your 2x2 picture was saved. Generate your resume again so it appears on it.');

    $path = $student->fresh()->id_photo_path;
    expect($path)->toMatch('#^id-photos/[A-Za-z0-9]{40}\.jpg$#')
        ->and(getimagesizefromstring(Storage::disk('private')->get($path)))->toMatchArray([0 => 600, 1 => 600]);
    Storage::disk('public')->assertMissing($path);
})->with(['jpeg', 'png', 'webp']);

it('rejects a PHP file renamed to .jpg for both photo types', function () {
    $student = photoStudent();
    $php = photoUpload("<?php system(\$_GET['c']); ?>", 'evil.jpg');

    $this->actingAs($student->user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => $php])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile_picture' => 'Only JPG, PNG, or WebP images are allowed.']);
    $this->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload("<?php echo 1; ?>", 'evil.jpg')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_photo' => 'Only JPG, PNG, or WebP images are allowed.']);

    // A real image header with a PHP payload still fails to decode.
    expect(fn () => app(ImageProcessor::class)->square("\xFF\xD8\xFF\xE0<?php echo 1; ?>", 400))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($student->fresh()->profile_picture)->toBeNull()
        ->and($student->fresh()->id_photo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('rejects photos over 5 MB with the size message', function () {
    $student = photoStudent();
    $tooBig = UploadedFile::fake()->create('big.jpg', ImageProcessor::MAX_KB + 1, 'image/jpeg');

    $this->actingAs($student->user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => $tooBig])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile_picture' => 'The photo is too large. The limit is 5 MB.']);
    $this->postJson(route('student.profile.id-photo.store'), ['id_photo' => UploadedFile::fake()->create('big.png', ImageProcessor::MAX_KB + 1, 'image/png')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_photo' => 'The photo is too large. The limit is 5 MB.']);
});

it('deletes the old files when photos are replaced or removed', function () {
    $student = photoStudent();
    $this->actingAs($student->user);

    $this->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload(photoBytes())])->assertOk();
    $first = $student->fresh()->profile_picture;
    $this->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload(photoBytes('png'), 'b.png')])->assertOk();
    $second = $student->fresh()->profile_picture;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
    $this->deleteJson(route('student.profile.delete-picture'))->assertOk()->assertJsonPath('success', true);
    Storage::disk('public')->assertMissing($second);
    expect($student->fresh()->profile_picture)->toBeNull();

    $this->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes())])->assertOk();
    $firstId = $student->fresh()->id_photo_path;
    $this->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes())])->assertOk();
    $secondId = $student->fresh()->id_photo_path;
    Storage::disk('private')->assertMissing($firstId);
    $this->deleteJson(route('student.profile.id-photo.destroy'))->assertOk();
    Storage::disk('private')->assertMissing($secondId);
    expect($student->fresh()->id_photo_path)->toBeNull();
});

it('fixes phone rotation and strips EXIF location data', function () {
    $original = photoWithExif();
    expect(@exif_read_data('data://image/jpeg;base64,'.base64_encode($original)))->toHaveKey('GPSLatitudeRef');
    $student = photoStudent();

    $this->actingAs($student->user)
        ->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload($original)])
        ->assertOk();

    $saved = Storage::disk('public')->get($student->fresh()->profile_picture);
    $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($saved));
    expect(is_array($exif) ? $exif : [])->not->toHaveKey('GPSLatitudeRef')
        ->not->toHaveKey('Orientation')
        ->and(str_contains($saved, "Exif\0\0"))->toBeFalse();
    // Orientation 6 turns the left (red) half to the top: red above, blue below.
    expect(isReddish(pixelAt($saved, 200, 60)))->toBeTrue()
        ->and(isBluish(pixelAt($saved, 200, 340)))->toBeTrue();
});

it('crops the 2x2 to the square the student positioned', function () {
    $student = photoStudent();

    // The right half of the 200x100 image is blue: a 100px square starting at x = 0.5.
    $this->actingAs($student->user)
        ->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes()), 'crop_x' => 0.5, 'crop_y' => 0, 'crop_size' => 0.5])
        ->assertOk();

    $saved = Storage::disk('private')->get($student->fresh()->id_photo_path);
    expect(isBluish(pixelAt($saved, 300, 300)))->toBeTrue()
        ->and(isBluish(pixelAt($saved, 30, 30)))->toBeTrue();
});

it('serves the 2x2 only to the student, their coordinator, and companies they applied to', function () {
    $institution = Institution::create(['name' => 'SCC']);
    $student = photoStudent(['institution_id' => $institution->id]);
    $this->actingAs($student->user)->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes())])->assertOk();
    $url = route('students.id-photo', $student);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $other = photoStudent();
    $this->actingAs($other->user)->get($url)->assertForbidden();
    // Another student's upload or remove only ever touches their own record.
    $this->deleteJson(route('student.profile.id-photo.destroy'))->assertOk();
    expect($student->fresh()->id_photo_path)->not->toBeNull();

    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $this->actingAs($coordinatorUser)->get($url)->assertOk();
    $outsider = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $outsider->id, 'institution_id' => Institution::create(['name' => 'Other'])->id]);
    $this->actingAs($outsider)->get($url)->assertForbidden();

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR']);
    $this->actingAs($employerUser)->get($url)->assertForbidden();
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 0, 'applied_at' => now()]);
    $this->get($url)->assertOk();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get($url)->assertForbidden();
});

/** Uploads a solid-colour profile picture and/or 2x2 for the signed-in student. */
function givePhotos(Student $student, bool $profile, bool $idPhoto): void
{
    test()->actingAs($student->user);
    if ($profile) {
        test()->postJson(route('student.profile.update-picture'), ['profile_picture' => photoUpload(photoBytes('jpeg', [20, 40, 220], [20, 40, 220]))])->assertOk();
    }
    if ($idPhoto) {
        test()->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes('jpeg', [220, 20, 40], [220, 20, 40]))])->assertOk();
    }
}

/** The resume photo for a student's current snapshot, decoded to JPEG bytes (or null). */
function resumePhotoOf(Student $student): ?string
{
    $photo = app(ResumeBuilderService::class)->resumePhoto(app(CompanyVisibleProfile::class)->resume($student->fresh()));

    return $photo === null ? null : base64_decode(substr($photo, strlen('data:image/jpeg;base64,')));
}

it('never puts the profile picture on the resume: a profile picture alone gives initials', function () {
    $student = photoStudent();
    givePhotos($student, profile: true, idPhoto: false);

    $content = app(CompanyVisibleProfile::class)->resume($student->fresh());
    expect($content)->not->toHaveKey('photo_path')
        ->and(json_encode($content))->not->toContain($student->fresh()->profile_picture)
        ->and(resumePhotoOf($student))->toBeNull()
        ->and(view('student.resume.pdf', ['content' => $content, 'photo' => null])->render())->toContain('<span class="photo-initials">AC</span>');

    $resume = app(ResumeBuilderService::class)->build($student->fresh());
    expect(Storage::disk('private')->get($resume->file_path))->not->toContain('/Subtype /Image');
});

it('puts the 2x2 on the resume', function () {
    $student = photoStudent();
    givePhotos($student, profile: false, idPhoto: true);

    expect(isReddish(pixelAt(resumePhotoOf($student), 200, 200)))->toBeTrue();
});

it('uses the 2x2 on the resume while the avatar elsewhere stays the profile picture', function () {
    $student = photoStudent();
    givePhotos($student, profile: true, idPhoto: true);

    expect(isReddish(pixelAt(resumePhotoOf($student), 200, 200)))->toBeTrue();
    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('src="/storage/'.$student->fresh()->profile_picture.'?v=', false);
    expect(\App\Support\Avatar::for($student->user->fresh())['url'])->toContain($student->fresh()->profile_picture);
});

it('falls back to initials without an error when the 2x2 file is missing', function () {
    $student = photoStudent();
    givePhotos($student, profile: true, idPhoto: true);
    Storage::disk('private')->delete($student->fresh()->id_photo_path);

    expect(resumePhotoOf($student))->toBeNull();
    $resume = app(ResumeBuilderService::class)->build($student->fresh());
    expect(Storage::disk('private')->get($resume->file_path))->toStartWith('%PDF')
        ->not->toContain('/Subtype /Image');
});

it('applies the same photo rule to the company version of the resume', function (bool $withIdPhoto) {
    $student = photoStudent();
    givePhotos($student, profile: true, idPhoto: $withIdPhoto);
    $resume = app(ResumeBuilderService::class)->build($student->fresh());
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR']);
    $service = app(ResumeBuilderService::class);

    // Before applying, the company gets a copy rendered on the fly (contact details hidden).
    $hidden = $service->pdfResponse($resume, $employerUser, download: false)->getContent();
    expect(str_contains($hidden, '/Subtype /Image'))->toBe($withIdPhoto);

    // After applying, the company gets the stored file: the same rule.
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'resume_id' => $resume->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 0, 'applied_at' => now()]);
    $this->actingAs($employerUser)
        ->get(route('employer.applicants.resume.preview', InternshipApplication::sole()))
        ->assertOk();
    expect(str_contains(Storage::disk('private')->get($resume->file_path), '/Subtype /Image'))->toBe($withIdPhoto);
})->with(['with a 2x2' => [true], 'profile picture only' => [false]]);

it('embeds the photo in the exported PDF', function () {
    $student = photoStudent();
    $this->actingAs($student->user)
        ->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes())])
        ->assertOk();

    $resume = app(ResumeBuilderService::class)->build($student->fresh());

    expect($resume->content['id_photo_path'])->toBe($student->fresh()->id_photo_path)
        ->and(Storage::disk('private')->get($resume->file_path))->toContain('/Subtype /Image');
});

it('leaves profile completion unchanged by the 2x2 picture', function () {
    $student = photoStudent();
    $before = $student->profileCompletionSummary();

    $this->actingAs($student->user)
        ->postJson(route('student.profile.id-photo.store'), ['id_photo' => photoUpload(photoBytes())])
        ->assertOk();
    $after = $student->fresh()->profileCompletionSummary();

    expect($after['percentage'])->toBe($before['percentage'])
        ->and($after['sections']['documents'])->toBe($before['sections']['documents']);
});

it('shows initials instead of a broken image when the photo file is missing', function () {
    $student = photoStudent(['profile_picture' => 'profiles/students/missing.jpg']);

    $this->actingAs($student->user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertDontSee('profiles/students/missing.jpg')
        ->assertSee('<span data-avatar-initials >AC</span>', false);
});
