<?php

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function postingModalEmployer(bool $verified = true): User
{
    $user = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $user->id,
        'company_name' => 'Modal Co',
        'contact_person' => 'HR',
        'verified_at' => $verified ? now() : null,
    ]);

    return $user;
}

it('opens the posting form as a dialog from both buttons, keeping the create page as the link target', function () {
    $html = $this->actingAs(postingModalEmployer())
        ->get(route('employer.internships.index'))
        ->assertOk()
        ->assertSee('id="newInternshipModal"', false)
        ->assertSee('role="dialog" aria-modal="true"', false)
        ->assertSee('Post New Internship')
        ->assertSee('Describe the role and the competencies you are looking for.')
        ->assertSeeInOrder(['Title', 'Status', 'Work Setup', 'Open to programs', 'Location', 'Duration', 'Application deadline',
            'Description', 'Responsibilities', 'General Requirements', 'Competency Requirements', 'Applicant requirements'])
        ->assertDontSee('Allowance')
        ->assertSee('form="newInternshipForm">Post Internship</button>', false)
        ->assertSee('Required')
        ->getContent();

    // "New Posting" and the empty-state "Post an internship" both open it, and both still link to the create page.
    expect(substr_count($html, 'href="'.route('employer.internships.create').'" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newInternshipModal"'))->toBe(2);
});

it('keeps plain links and no dialog for a company that is not verified yet', function () {
    $this->actingAs(postingModalEmployer(verified: false))
        ->get(route('employer.internships.index'))
        ->assertOk()
        ->assertDontSee('id="newInternshipModal"', false)
        ->assertDontSee('data-bs-target="#newInternshipModal"', false)
        ->assertSee(route('employer.internships.create'), false);
});

it('returns field errors to the dialog and saves a valid posting', function () {
    $user = postingModalEmployer();

    $this->actingAs($user)
        ->postJson(route('employer.internships.store'), ['_modal' => 'newInternship', 'status' => 'open'], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description', 'work_setup']);

    $this->post(route('employer.internships.store'), [
        '_modal' => 'newInternship',
        'title' => 'Modal Intern',
        'description' => 'Build things.',
        'work_setup' => 'onsite',
        'status' => 'open',
        'requirement_names' => [''],
        'requirement_levels' => ['beginner'],
    ])->assertRedirect(route('employer.internships.index'));

    expect(Internship::where('title', 'Modal Intern')->exists())->toBeTrue();
    $this->get(route('employer.internships.index'))
        ->assertSee('Internship posted successfully.')
        ->assertSee('data-internship-id="'.Internship::where('title', 'Modal Intern')->value('id').'"', false);
});

it('reopens the dialog with its errors after a plain (no-script) submit fails', function () {
    $this->actingAs(postingModalEmployer())
        ->from(route('employer.internships.index'))
        ->post(route('employer.internships.store'), ['_modal' => 'newInternship', 'status' => 'open'])
        ->assertRedirect(route('employer.internships.index'));

    $this->get(route('employer.internships.index'))
        ->assertSee('data-sb-open-on-load', false)
        ->assertSee('is-invalid', false);
});

it('still serves the create page as a fallback with the same fields', function () {
    $this->actingAs(postingModalEmployer())
        ->get(route('employer.internships.create'))
        ->assertOk()
        ->assertSee('Post New Internship')
        ->assertSee('data-add-requirement', false)
        ->assertSee('Post Internship')
        ->assertDontSee('Allowance');
});

it('keeps the allowance field when editing an existing posting', function () {
    $user = postingModalEmployer();
    $internship = Internship::create([
        'employer_id' => $user->employer->id,
        'title' => 'Paid Intern',
        'description' => 'x',
        'work_setup' => 'onsite',
        'status' => 'open',
        'allowance' => 5000,
    ]);

    $this->actingAs($user)
        ->get(route('employer.internships.edit', $internship))
        ->assertOk()
        ->assertSee('Allowance (₱)')
        ->assertSee('value="5000.00"', false);
});

/** The detail panel's first render (the templates after it hold every posting). */
function postingPanel(string $html): string
{
    return \Illuminate\Support\Str::betweenFirst($html, 'id="postingDetailPanel"', '<template');
}

function postingFor(User $user, array $attributes = []): Internship
{
    return Internship::create([
        'employer_id' => $user->employer->id,
        'title' => 'Posting '.\Illuminate\Support\Str::random(4),
        'description' => 'x',
        'work_setup' => 'hybrid',
        'status' => 'open',
        ...$attributes,
    ]);
}

it('lists postings by status with real counts and shows the selected one in the detail panel', function () {
    $user = postingModalEmployer();
    postingFor($user, ['title' => 'Old Closed Role', 'status' => 'closed', 'created_at' => now()->subDays(3)]);
    $open = postingFor($user, ['title' => 'Web Intern', 'location' => 'Makati City', 'duration' => '3 months', 'created_at' => now()->subDay()]);
    $open->requirementsList()->create(['requirement_name' => 'HTML & CSS', 'required_level' => 'intermediate']);

    $html = $this->actingAs($user)->get(route('employer.internships.index'))
        ->assertOk()
        ->assertSeeInOrder(['Open <span class="sbx-tab-count">1</span>', 'Closed <span class="sbx-tab-count">1</span>'], false)
        ->getContent();
    $panel = postingPanel($html);

    expect($panel)->toContain('Web Intern')
        ->toContain('Makati City · Hybrid · 3 months')
        ->toContain('HTML &amp; CSS · Intermediate')
        ->toContain('All programs')
        ->toContain('No deadline')
        ->toContain('No applicants yet. Students who match will see this posting first.')
        ->toContain('aria-label="Edit Web Intern"')
        ->toContain('aria-label="Close Web Intern"')
        ->toContain('data-confirm-title="Close posting"')
        ->toContain('aria-label="Delete Web Intern"')
        ->not->toContain('Old Closed Role');
});

it('shows the latest applicants and the selected programs', function () {
    $user = postingModalEmployer();
    $posting = postingFor($user, ['title' => 'Data Intern', 'expires_at' => now()->addDays(10)->endOfDay()]);
    $program = \App\Models\Program::create(['code' => 'BSIT', 'name' => 'BS Information Technology', 'required_internship_hours' => 486]);
    $posting->programs()->sync([$program->id]);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Lia Santos']);
    $student = \App\Models\Student::create(['user_id' => $studentUser->id]);
    $posting->applications()->create(['student_id' => $student->id, 'status' => 'applied', 'applied_at' => now()]);

    $panel = postingPanel($this->actingAs($user)->get(route('employer.internships.index'))->getContent());

    expect($panel)->toContain($program->display_name)
        ->not->toContain('All programs')
        ->toContain('Lia Santos')
        ->toContain(route('employer.applicants.index'))
        ->toContain(now()->addDays(10)->format('M d, Y'));
});

it('keeps the selected posting and tab from the address, and shows empty tabs and no postings', function () {
    $user = postingModalEmployer();
    $this->actingAs($user)->get(route('employer.internships.index'))
        ->assertSee('No internship postings yet')
        ->assertSee('Post an internship');

    $closed = postingFor($user, ['title' => 'Closed Role', 'status' => 'closed']);
    postingFor($user, ['title' => 'Open Role']);

    $html = $this->get(route('employer.internships.index', ['posting' => $closed->id]))->getContent();
    expect(postingPanel($html))->toContain('Closed Role')->not->toContain('aria-label="Close Closed Role"')
        ->and($html)->toMatch('/class="sb-section-tab sbx-tab active"\s+data-posting-tab="closed"/');

    $closed->delete();
    $this->get(route('employer.internships.index', ['tab' => 'closed']))
        ->assertSee('No closed postings.');
});
