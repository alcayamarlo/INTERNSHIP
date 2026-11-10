<?php

use App\Enums\UserRole;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use App\Services\ResumeBuilderService;
use App\Services\ResumeContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

function editorStudent(string $email = 'ana@example.com'): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz', 'email' => $email, 'phone' => '09171234567']);

    return Student::create([
        'user_id' => $user->id,
        'program' => 'BS Information Technology',
        'career_objectives' => 'Profile summary.',
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
    ]);
}

function editorResume(Student $student): Resume
{
    return app(ResumeBuilderService::class)->build($student->fresh());
}

/** Editor data as the page sends it, starting from the resume's own content. */
function editorPayload(Resume $resume, array $overrides = []): array
{
    return array_replace_recursive(app(ResumeContent::class)->toEditor($resume->content), $overrides);
}

it('shows Edit for current resumes and explains why older ones cannot be edited', function () {
    $student = editorStudent();
    $resume = editorResume($student);
    Resume::create(['student_id' => $student->id, 'content' => ['name' => 'Old'], 'file_path' => 'resumes/legacy.pdf', 'generated_at' => now()->subDay()]);

    $this->actingAs($student->user)
        ->get(route('student.resume.index'))
        ->assertOk()
        ->assertSee('href="'.route('student.resume.edit', $resume).'"', false)
        ->assertSee('Older format. Generate a new resume to edit it.');
});

it('opens the editor with the form, the live preview, and all four actions', function () {
    $student = editorStudent();
    $resume = editorResume($student);

    $this->actingAs($student->user)
        ->get(route('student.resume.edit', $resume))
        ->assertOk()
        ->assertSee('value="Ana Cruz"', false)
        ->assertSee('value="ana@example.com"', false)
        ->assertSee('data-preview-frame', false)
        ->assertSeeInOrder(['Personal info', 'Career summary', 'Education', 'Skills', 'Work experience', 'Projects', 'Certifications', 'Character references'])
        ->assertSeeInOrder(['Cancel', 'Download PDF', 'Save as new resume', 'Save changes'])
        ->assertSee("Also update my profile's career summary", false);
});

it('renders the live preview from unsaved edits without storing anything', function () {
    $student = editorStudent();
    $resume = editorResume($student);
    $before = $resume->content;

    $this->actingAs($student->user)
        ->postJson(route('student.resume.preview', $resume), editorPayload($resume, ['summary' => 'Fresh preview summary.', 'name' => '']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Fresh preview summary.');

    expect($resume->fresh()->content)->toBe($before);
});

it('saves edits to that resume only, re-renders its PDF, and leaves the profile alone', function () {
    $student = editorStudent();
    $resume = editorResume($student);
    $oldPath = $resume->file_path;

    $this->actingAs($student->user)
        ->putJson(route('student.resume.update', $resume), editorPayload($resume, [
            'name' => 'Ana B. Cruz',
            'summary' => 'Edited summary for this resume.',
            'skills' => ['Customer service', '  ', 'Excel'],
            'experiences' => [
                ['title' => 'IT Support Assistant', 'rows' => [['label' => 'Organization', 'value' => 'City Hall']], 'skills' => [], 'description' => 'Fixed printers.'],
                ['title' => '', 'rows' => [], 'skills' => [], 'description' => ''], // blank: dropped
            ],
            'id_photo_path' => 'someone-elses/photo.jpg', // not editable: ignored
        ]))
        ->assertOk()
        ->assertJsonPath('message', 'Changes saved.');

    $resume->refresh();
    expect($resume->content['name'])->toBe('Ana B. Cruz')
        ->and($resume->content['initials'])->toBe('AC')
        ->and($resume->content['summary'])->toBe('Edited summary for this resume.')
        ->and($resume->content['skills'])->toBe(['Customer service', 'Excel'])
        ->and($resume->content['experiences'])->toHaveCount(1)
        ->and($resume->content['experiences'][0]['rows'])->toBe(['Organization' => 'City Hall'])
        ->and($resume->content['id_photo_path'])->toBeNull()
        ->and($resume->content['edited_at'])->not->toBeNull()
        // The PDF was rendered again from the edited content, to a new file.
        ->and($resume->file_path)->not->toBe($oldPath)
        ->and(Storage::disk('private')->exists($resume->file_path))->toBeTrue()
        ->and(Storage::disk('private')->exists($oldPath))->toBeFalse()
        // The profile is untouched.
        ->and($student->fresh()->career_objectives)->toBe('Profile summary.')
        ->and($student->user->fresh()->name)->toBe('Ana Cruz');

    $this->get(route('student.resume.index'))->assertSee('data-resume-edited', false);
});

it('also updates the profile summary when the student opts in', function () {
    $student = editorStudent();
    $resume = editorResume($student);

    $this->actingAs($student->user)
        ->putJson(route('student.resume.update', $resume), editorPayload($resume, ['summary' => 'Shared summary.', 'update_profile_summary' => true]))
        ->assertOk()
        ->assertJsonPath('message', 'Changes saved. Your profile summary was updated too.');

    expect($student->fresh()->career_objectives)->toBe('Shared summary.');
});

it('requires a name and contact email, complete rows, and entry titles', function () {
    $student = editorStudent();
    $resume = editorResume($student);
    $before = $resume->content;

    $this->actingAs($student->user)
        ->putJson(route('student.resume.update', $resume), editorPayload($resume, [
            'name' => ' ',
            'contact' => ['email' => 'not-an-email'],
            'personal' => [['label' => 'Birthday', 'value' => ''], ['label' => 'Sex', 'value' => 'Female'], ['label' => 'sex', 'value' => 'F']],
            'projects' => [['title' => '', 'rows' => [['label' => 'Tools used', 'value' => 'Laravel']], 'skills' => [], 'description' => '']],
            'summary' => str_repeat('a', 2001),
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'name' => 'Enter your name.',
            'contact.email' => 'Enter a valid email address.',
            'personal.0.value' => 'Add a value, or clear this row.',
            'personal' => 'Each label in this list must be different.',
            'projects.0.title' => 'Add a title, or remove this entry.',
            'summary',
        ]);

    expect($resume->fresh()->content)->toBe($before);
});

it('saves as a new resume, keeps the original, and still keeps only the 3 latest', function () {
    $student = editorStudent();
    $oldest = editorResume($student);
    $this->travel(1)->minutes();
    $middle = editorResume($student);
    $this->travel(1)->minutes();
    $original = editorResume($student);
    $originalContent = $original->content;
    $this->travel(1)->minutes();

    $response = $this->actingAs($student->user)
        ->postJson(route('student.resume.copy', $original), editorPayload($original, ['summary' => 'Copy summary.']))
        ->assertCreated();

    $copy = Resume::latest('id')->first();
    $response->assertJsonPath('edit_url', route('student.resume.edit', $copy));

    expect($copy->content['summary'])->toBe('Copy summary.')
        ->and($original->fresh()->content)->toBe($originalContent)
        ->and(Resume::where('student_id', $student->id)->count())->toBe(ResumeBuilderService::MAX_GENERATED_RESUMES)
        ->and(Resume::find($oldest->id))->toBeNull()
        ->and(Resume::find($middle->id))->not->toBeNull();
});

it('downloads a PDF of unsaved edits without storing them', function () {
    $student = editorStudent();
    $resume = editorResume($student);
    $before = $resume->content;

    $response = $this->actingAs($student->user)
        ->post(route('student.resume.pdf', $resume), editorPayload($resume, ['summary' => 'Only in the download.']), ['Accept' => 'application/json'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF')
        ->and($resume->fresh()->content)->toBe($before);
});

it('keeps other students and older resume formats out of the editor', function () {
    $owner = editorStudent();
    $resume = editorResume($owner);
    $intruder = editorStudent('intruder@example.com');

    $this->actingAs($intruder->user)->get(route('student.resume.edit', $resume))->assertForbidden();
    $this->putJson(route('student.resume.update', $resume), editorPayload($resume))->assertForbidden();
    $this->postJson(route('student.resume.preview', $resume), editorPayload($resume))->assertForbidden();
    $this->postJson(route('student.resume.copy', $resume), editorPayload($resume))->assertForbidden();
    $this->postJson(route('student.resume.pdf', $resume), editorPayload($resume))->assertForbidden();

    $legacy = Resume::create(['student_id' => $owner->id, 'content' => ['name' => 'Old'], 'file_path' => 'resumes/legacy.pdf', 'generated_at' => now()]);
    $this->actingAs($owner->user)->get(route('student.resume.edit', $legacy))
        ->assertRedirect(route('student.resume.index'))
        ->assertSessionHas('error');
    $this->putJson(route('student.resume.update', $legacy), ['name' => 'X', 'contact' => ['email' => 'x@example.com']])->assertStatus(422);
});
