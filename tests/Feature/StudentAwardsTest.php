<?php

use App\Enums\AwardLevel;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentAward;
use App\Models\User;
use App\Services\CompanyVisibleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function awardStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create([
        'user_id' => $user->id,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
        ...$attributes,
    ]);
}

function addAward(Student $student, AwardLevel $level, string $title, ?string $year = null, array $extra = []): StudentAward
{
    return $student->awards()->create(['level' => $level, 'title' => $title, 'year' => $year, ...$extra]);
}

function awardResumeHtml(Student $student): string
{
    return view('student.resume.pdf', [
        'content' => app(CompanyVisibleProfile::class)->resume($student->fresh()),
        'photo' => null,
    ])->render();
}

it('groups awards as College, Senior High, Junior High, then Elementary', function () {
    $student = awardStudent();
    addAward($student, AwardLevel::Elementary, 'Best in Math', '2012');
    addAward($student, AwardLevel::SeniorHigh, 'With High Honors', '2021');
    addAward($student, AwardLevel::College, "Dean's Lister", '2024');
    addAward($student, AwardLevel::JuniorHigh, 'Science Quiz Champion', '2017');

    $labels = array_column(StudentAward::grouped($student->awards()->get()), 'label');
    expect($labels)->toBe(['College', 'Senior High School', 'Junior High School', 'Elementary']);

    $html = awardResumeHtml($student);
    $positions = array_map(fn (string $text) => strpos($html, $text), ['<div class="bar">Honors / awards</div>', "Dean&#039;s Lister", 'With High Honors', 'Science Quiz Champion', 'Best in Math']);
    expect($positions)->each->not->toBeFalse()
        ->and($positions)->toBe(collect($positions)->sort()->values()->all());
});

it('lists the newest year first inside a level, with present ranges on top and no year last', function () {
    $student = awardStudent();
    addAward($student, AwardLevel::College, 'Old Award', '2021');
    addAward($student, AwardLevel::College, 'No Year Award');
    addAward($student, AwardLevel::College, 'Ongoing Scholar', '2023-present');
    addAward($student, AwardLevel::College, 'Recent Award', '2024');
    addAward($student, AwardLevel::College, 'Range Award', '2022-2023');

    $titles = array_map(fn (StudentAward $award) => $award->title, StudentAward::grouped($student->awards()->get())[0]['awards']);

    expect($titles)->toBe(['Ongoing Scholar', 'Recent Award', 'Range Award', 'Old Award', 'No Year Award']);
});

it('shows event, year, and detail on a smaller line under the award', function () {
    $student = awardStudent();
    addAward($student, AwardLevel::College, "Dean's Lister", '2023-2024', ['event' => "St. Cecilia's College", 'detail' => 'GWA: 1.45']);

    expect(awardResumeHtml($student))
        ->toContain('<p class="entry-meta">St. Cecilia&#039;s College · 2023-2024 · GWA: 1.45</p>');
});

it('hides levels with no awards and the whole section when there are none', function () {
    $student = awardStudent();
    expect(awardResumeHtml($student))->not->toContain('<div class="bar">Honors / awards</div>');

    addAward($student, AwardLevel::College, "Dean's Lister", '2024');
    $html = awardResumeHtml($student);

    expect($html)->toContain('<div class="bar">Honors / awards</div>')
        ->toContain('>College<')
        ->not->toContain('Senior High School')
        ->not->toContain('Junior High School')
        ->not->toContain('>Elementary<');
});

it('copies the old paragraph into one Other entry and keeps the old text', function () {
    $paragraph = "With Honors (2021), Dean's Lister 2023-2024, Best Thesis";
    $student = awardStudent(['honors_awards' => $paragraph]);
    awardStudent(['honors_awards' => '   ']);
    awardStudent();

    $migration = require database_path('migrations/2026_11_01_000001_create_student_awards_table.php');
    $migration->down();
    $migration->up();

    $rows = DB::table('student_awards')->get();
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->student_id)->toBe($student->id)
        ->and($rows[0]->level)->toBe('other')
        ->and($rows[0]->title)->toBe($paragraph)
        ->and($student->fresh()->honors_awards)->toBe($paragraph);

    // Rolling back only drops the new table; the old text stays.
    $migration->down();
    expect($student->fresh()->honors_awards)->toBe($paragraph);
    $migration->up();
});

it('lets a student add, edit, and remove awards one at a time', function () {
    $student = awardStudent();
    $this->actingAs($student->user);

    $this->postJson(route('student.awards.store'), ['level' => 'college', 'title' => "Dean's Lister", 'year' => '2023 - Present', 'detail' => 'GWA: 1.45'])
        ->assertCreated()
        ->assertJsonPath('count', 1);
    $award = $student->awards()->first();
    expect($award->year)->toBe('2023-present');

    $this->putJson(route('student.awards.update', $award), ['level' => 'senior_high', 'title' => 'With High Honors', 'year' => '2021'])
        ->assertOk();
    expect($award->fresh()->level)->toBe(AwardLevel::SeniorHigh)
        ->and($award->fresh()->title)->toBe('With High Honors');

    $this->postJson(route('student.awards.store'), ['level' => 'college', 'title' => 'Bad Year', 'year' => 'last year'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['year' => 'Use a year like 2024, 2023-2024, or 2023-present.']);
    $this->postJson(route('student.awards.store'), ['level' => 'other', 'title' => 'Sneaky'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('level');

    $this->deleteJson(route('student.awards.destroy', $award))->assertOk()->assertJsonPath('count', 0);
    expect($student->awards()->count())->toBe(0);
});

it('does not let a student change another student\'s awards', function () {
    $owner = awardStudent();
    $award = addAward($owner, AwardLevel::College, "Dean's Lister", '2024');

    $this->actingAs(awardStudent()->user);
    $this->putJson(route('student.awards.update', $award), ['level' => 'college', 'title' => 'Hacked'])->assertForbidden();
    $this->deleteJson(route('student.awards.destroy', $award))->assertForbidden();

    expect($award->fresh()->title)->toBe("Dean's Lister");
});

it('shows the Add award form and the grouped list on the Academic tab', function () {
    $student = awardStudent();
    addAward($student, AwardLevel::College, "Dean's Lister", '2024');

    $this->actingAs($student->user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('data-collection-open="awards"', false)
        ->assertSee('Add award')
        ->assertSee("Dean's Lister")
        ->assertDontSee('name="honors_awards"', false);
});
