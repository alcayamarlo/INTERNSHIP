<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Institution;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function feedbackFixture(): array
{
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'year_level' => '4th Year', 'major_subjects_passed' => true]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer, 'email' => 'private.hr@employer.test']);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Public Facing Co', 'contact_person' => 'Secret Person']);

    $open = Internship::create(['employer_id' => $employer->id, 'title' => 'Open Web Intern', 'description' => 'Build web things.', 'work_setup' => 'remote', 'status' => 'open', 'location' => 'Cebu City']);
    $closed = Internship::create(['employer_id' => $employer->id, 'title' => 'Closed Intern Role', 'description' => 'Gone.', 'work_setup' => 'remote', 'status' => 'closed']);

    return compact('studentUser', 'student', 'employerUser', 'employer', 'open', 'closed');
}

// ---------------------------------------------------------------- A. breadcrumbs + My Applications under Internships

it('has no separate My Applications sidebar item but keeps it reachable inside Internships', function () {
    $f = feedbackFixture();
    $html = $this->actingAs($f['studentUser'])->get(route('student.internships.index'))->assertOk()->getContent();

    preg_match('/<nav class="sb-nav">.*?<\/nav>/s', $html, $sidebar);
    expect($sidebar[0])->not->toContain(route('student.applications.index'))
        ->and($sidebar[0])->toContain(route('student.internships.index'));
    expect($html)->toContain('sb-section-tabs')->toContain(route('student.applications.index'));

    // Old URL and route name keep working, and Internships stays highlighted there.
    $apps = $this->actingAs($f['studentUser'])->get(route('student.applications.index'))->assertOk()->getContent();
    preg_match('/<nav class="sb-nav">.*?<\/nav>/s', $apps, $nav);
    expect($nav[0])->toMatch('/sb-nav-link active[^>]*href="[^"]*student\/internships"/s');
});

it('renders clickable breadcrumbs at every level with the current page as plain text', function () {
    $f = feedbackFixture();
    $application = InternshipApplication::create(['internship_id' => $f['open']->id, 'student_id' => $f['student']->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 50]);

    $this->actingAs($f['studentUser'])->get(route('student.internships.index'))
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee('href="'.route('student.dashboard').'"', false)
        ->assertSee('aria-current="page"', false);

    $this->actingAs($f['studentUser'])->get(route('student.internships.show', $f['open']))
        ->assertSee('Open Web Intern', false)
        ->assertSee('href="'.route('student.internships.index').'"', false);

    $show = $this->actingAs($f['studentUser'])->get(route('student.applications.show', $application))->assertOk()->getContent();
    preg_match('/<nav class="sb-breadcrumbs".*?<\/nav>/s', $show, $crumbs);
    expect($crumbs[0])->toContain(route('student.dashboard'))
        ->toContain(route('student.internships.index'))
        ->toContain(route('student.applications.index'))
        ->and(substr_count($crumbs[0], 'aria-current="page"'))->toBe(1)
        ->and($crumbs[0])->not->toContain('href="'.route('student.applications.show', $application).'"');
});

// ---------------------------------------------------------------- B. modals

it('offers the competency form in a modal and re-opens it with errors after a failed save', function () {
    $f = feedbackFixture();

    $this->actingAs($f['studentUser'])->get(route('student.competencies.index'))
        ->assertOk()
        ->assertSee('id="addCompetencyModal"', false)
        ->assertSee('data-bs-target="#addCompetencyModal"', false)
        ->assertDontSee('data-sb-open-on-load', false);

    $this->actingAs($f['studentUser'])->from(route('student.competencies.index'))
        ->post(route('student.competencies.store'), ['_modal' => 'competency', 'name' => ''])
        ->assertRedirect(route('student.competencies.index'))
        ->assertSessionHasErrors('name');

    $this->actingAs($f['studentUser'])->followingRedirects()->from(route('student.competencies.index'))
        ->post(route('student.competencies.store'), ['_modal' => 'competency', 'name' => ''])
        ->assertOk()
        ->assertSee('data-sb-open-on-load', false)
        ->assertSee('Please fix the highlighted fields');
});

it('puts the daily entry and weekly journal forms in modals that re-open after validation errors', function () {
    $f = feedbackFixture();
    $institution = Institution::create(['name' => 'OJT University', 'address' => 'Cebu', 'contact_email' => 'ojt@example.edu']);
    $f['student']->update(['institution_id' => $institution->id]);
    $application = InternshipApplication::create(['internship_id' => $f['open']->id, 'student_id' => $f['student']->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);
    $placement = Placement::create([
        'application_id' => $application->id, 'student_id' => $f['student']->id, 'employer_id' => $f['employer']->id, 'internship_id' => $f['open']->id,
        'start_date' => now()->subDays(10)->toDateString(), 'required_hours' => 16, 'status' => 'active',
    ]);

    $this->actingAs($f['studentUser'])->get(route('student.ojt.index'))
        ->assertOk()
        ->assertSee('id="dailyEntryModal"', false)
        ->assertSee('id="weeklyJournalModal"', false)
        ->assertSee('data-bs-target="#weeklyJournalModal"', false);

    $this->actingAs($f['studentUser'])->from(route('student.ojt.index'))
        ->post(route('student.ojt.journals.store', $placement), ['_modal' => 'weekly', 'week_start' => now()->toDateString(), 'title' => ''])
        ->assertSessionHasErrors('title')
        ->assertSessionHasInput('_modal', 'weekly');

    $this->actingAs($f['studentUser'])->followingRedirects()->from(route('student.ojt.index'))
        ->post(route('student.ojt.journals.store', $placement), ['_modal' => 'weekly', 'week_start' => now()->toDateString(), 'title' => ''])
        ->assertOk()
        ->assertSee('data-sb-open-on-load', false);
});

// ---------------------------------------------------------------- C. public browse + login-first apply

it('shows only open internships, with public-safe fields, to guests on the landing page and listing', function () {
    $f = feedbackFixture();

    $this->get(route('home'))->assertOk()
        ->assertSee('Browse internships')
        ->assertSee('Open Web Intern')->assertSee('Public Facing Co')->assertSee('Cebu City')
        ->assertDontSee('Closed Intern Role')
        ->assertDontSee('private.hr@employer.test')->assertDontSee('Secret Person')
        ->assertSee('Logging in as a student is required to apply.');

    $this->get(route('internships.index'))->assertOk()->assertSee('Open Web Intern')->assertDontSee('Closed Intern Role');
    $this->get(route('internships.index', ['search' => 'nothing-matches-this']))->assertOk()->assertSee('No open internships right now. Check back soon.');
    $this->get(route('internships.show', $f['open']))->assertOk()->assertSee('Build web things.')->assertDontSee('private.hr@employer.test');
    $this->get(route('internships.show', $f['closed']))->assertNotFound();
});

it('shows a friendly empty state when nothing is open', function () {
    $this->get(route('home'))->assertOk()->assertSee('No open internships right now. Check back soon.');
});

it('sends a guest to login with a message and returns them to the apply page afterwards', function () {
    $f = feedbackFixture();
    $applyUrl = route('internships.apply', $f['open']);

    $this->get($applyUrl)->assertRedirect(route('login'))->assertSessionHas('status', 'Please log in to apply.');
    expect(session('url.intended'))->toBe($applyUrl);

    $this->post('/login', ['email' => $f['studentUser']->email, 'password' => 'password'])->assertRedirect($applyUrl);

    $this->get($applyUrl)->assertRedirect(route('student.internships.show', $f['open']));
});

it('lets a logged-in student go straight to the existing apply flow', function () {
    $f = feedbackFixture();

    $this->actingAs($f['studentUser'])->get(route('internships.apply', $f['open']))
        ->assertRedirect(route('student.internships.show', $f['open']));
});

it('does not let coordinators or employers apply, in the UI or on the protected route', function () {
    $f = feedbackFixture();
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);

    $this->actingAs($coordinator)->get(route('internships.index'))->assertOk()
        ->assertSee('Only students can apply')
        ->assertDontSee(route('internships.apply', $f['open']), false);

    $this->actingAs($coordinator)->get(route('internships.apply', $f['open']))
        ->assertRedirect(route('internships.show', $f['open']))->assertSessionHas('info', 'Only students can apply.');

    $this->actingAs($f['employerUser'])->post(route('student.internships.apply', $f['open']))->assertForbidden();
    auth()->logout();
    $this->post(route('student.internships.apply', $f['open']))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------- D. 60-30-10 palette

it('loads the same dashboard stylesheet for every role', function () {
    $f = feedbackFixture();
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    \App\Models\Coordinator::create(['user_id' => $coordinator->id, 'institution_id' => Institution::create(['name' => 'Dash U', 'address' => 'Cebu', 'contact_email' => 'd@example.edu'])->id]);

    foreach ([[$f['studentUser'], 'student.dashboard'], [$coordinator, 'coordinator.dashboard'], [$f['employerUser'], 'employer.dashboard']] as [$user, $route]) {
        $this->actingAs($user)->get(route($route))->assertOk()->assertSee('css/skillbridge-dashboard.css', false);
    }

    expect(file_exists(public_path('css/skillbridge-student.css')))->toBeFalse();
    $css = file_get_contents(public_path('css/skillbridge-dashboard.css'));
    expect($css)->toContain('--sb-red-60')->toContain('--sb-white-30')->toContain('--sb-black-10');
});
