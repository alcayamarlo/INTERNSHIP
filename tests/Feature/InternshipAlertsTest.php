<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\InternshipAlertService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function alertsFixture(): array
{
    $it = Program::firstOrCreate(['code' => 'BSIT'], ['name' => 'BS Information Technology', 'required_internship_hours' => 486]);
    $hm = Program::firstOrCreate(['code' => 'BSHM'], ['name' => 'BS Hospitality Management', 'required_internship_hours' => 600]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['verified_at' => now(), 'user_id' => $employerUser->id, 'company_name' => 'Alert Co', 'contact_person' => 'Alert']);

    $makeStudent = function (Program $program, ?string $skill) {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $student = Student::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
            'program' => $program->name,
            'year_level' => '4th Year',
            'major_subjects_passed' => true,
        ]);
        if ($skill) {
            StudentCompetency::create([
                'student_id' => $student->id,
                'name' => $skill,
                'category' => CompetencyCategory::Technical,
                'proficiency_level' => ProficiencyLevel::Advanced,
                'verification_status' => 'verified',
            ]);
        }

        return $user;
    };

    return [
        'it' => $it,
        'hm' => $hm,
        'employer' => $employer,
        'employerUser' => $employerUser,
        'matching' => $makeStudent($it, 'Laravel Framework'),
        'noSkill' => $makeStudent($it, null),
        'otherProgram' => $makeStudent($hm, 'Laravel Framework'),
    ];
}

function postingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laravel Intern',
        'description' => 'Build things.',
        'work_setup' => 'onsite',
        'status' => 'open',
        'requirement_names' => ['Laravel Framework'],
        'requirement_levels' => [ProficiencyLevel::Beginner->value],
    ], $overrides);
}

it('alerts only matching students in the posting programs when an internship is posted', function () {
    $f = alertsFixture();

    $this->actingAs($f['employerUser'])
        ->post(route('employer.internships.store'), postingPayload(['program_ids' => [$f['it']->id]]))
        ->assertRedirect();

    $internship = Internship::where('title', 'Laravel Intern')->firstOrFail();
    expect($internship->alerts_sent_at)->not->toBeNull();

    $alerted = AppNotification::where('type', 'internship_alert')->pluck('user_id')->all();
    expect($alerted)->toBe([$f['matching']->id]);

    $notification = AppNotification::where('type', 'internship_alert')->first();
    expect($notification->data['internship_id'])->toBe($internship->id)
        ->and($notification->message)->toContain('100%');

    // The notification links to the posting.
    $this->actingAs($f['matching'])->get(route('notifications.show', $notification))
        ->assertOk()
        ->assertSee(route('student.internships.show', $internship), false);
});

it('does not alert for closed postings, and alerts once when the posting is opened', function () {
    $f = alertsFixture();

    $this->actingAs($f['employerUser'])
        ->post(route('employer.internships.store'), postingPayload(['status' => 'closed']));
    $internship = Internship::where('title', 'Laravel Intern')->firstOrFail();
    expect(AppNotification::where('type', 'internship_alert')->count())->toBe(0);

    $this->actingAs($f['employerUser'])
        ->put(route('employer.internships.update', $internship), postingPayload());
    $this->actingAs($f['employerUser'])
        ->put(route('employer.internships.update', $internship), postingPayload(['description' => 'Edited.']));

    // No program limit: the BSHM student with the skill matches too, but each only once.
    expect(AppNotification::where('type', 'internship_alert')->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$f['matching']->id, $f['otherProgram']->id])->sort()->values()->all());
});

it('skips students who already have a placement', function () {
    $f = alertsFixture();
    $other = Internship::create(['employer_id' => $f['employer']->id, 'title' => 'Earlier', 'description' => 'x', 'status' => 'closed']);
    $application = $other->applications()->create(['student_id' => $f['matching']->student->id, 'status' => 'accepted']);
    \Illuminate\Support\Facades\DB::table('placements')->insert([
        'application_id' => $application->id,
        'student_id' => $f['matching']->student->id,
        'employer_id' => $f['employer']->id,
        'internship_id' => $other->id,
        'status' => 'active',
        'start_date' => now()->toDateString(),
        'required_hours' => 486,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($f['employerUser'])
        ->post(route('employer.internships.store'), postingPayload(['program_ids' => [$f['it']->id]]));

    expect(AppNotification::where('type', 'internship_alert')->count())->toBe(0);
});

it('saves the application deadline as the end of the chosen day and rejects past dates', function () {
    $f = alertsFixture();
    $date = now()->addDays(10)->format('Y-m-d');

    $this->actingAs($f['employerUser'])
        ->post(route('employer.internships.store'), postingPayload(['expires_at' => $date]))
        ->assertSessionHasNoErrors();
    $internship = Internship::where('title', 'Laravel Intern')->firstOrFail();
    expect($internship->expires_at->format('Y-m-d H:i:s'))->toBe($date.' 23:59:59');

    $this->actingAs($f['employerUser'])
        ->post(route('employer.internships.store'), postingPayload(['title' => 'Past', 'expires_at' => now()->subDay()->format('Y-m-d')]))
        ->assertSessionHasErrors('expires_at');
});

it('reminds matching students who have not applied, and the company, once before the deadline', function () {
    $f = alertsFixture();
    $internship = Internship::create([
        'employer_id' => $f['employer']->id,
        'title' => 'Closing Soon Role',
        'description' => 'x',
        'status' => 'open',
        'expires_at' => now()->addDays(2)->endOfDay(),
        'alerts_sent_at' => now(),
    ]);
    $internship->requirementsList()->create(['requirement_name' => 'Laravel Framework', 'required_level' => ProficiencyLevel::Beginner]);

    $farOff = Internship::create([
        'employer_id' => $f['employer']->id,
        'title' => 'Far Off Role',
        'description' => 'x',
        'status' => 'open',
        'expires_at' => now()->addDays(20)->endOfDay(),
        'alerts_sent_at' => now(),
    ]);

    $this->artisan('internships:remind-deadlines', ['--days' => 3])->assertSuccessful();
    $this->artisan('internships:remind-deadlines', ['--days' => 3])->assertSuccessful();

    $reminders = AppNotification::where('type', 'internship_deadline')->get();
    expect($reminders->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$f['matching']->id, $f['otherProgram']->id, $f['employerUser']->id])->sort()->values()->all())
        ->and($reminders->pluck('data.internship_id')->unique()->all())->toBe([$internship->id])
        ->and($internship->fresh()->deadline_reminded_at)->not->toBeNull()
        ->and($farOff->fresh()->deadline_reminded_at)->toBeNull();
});

it('does not remind students who already applied, and re-arms the reminder when the deadline moves', function () {
    $f = alertsFixture();
    $internship = Internship::create([
        'employer_id' => $f['employer']->id,
        'title' => 'Laravel Intern',
        'description' => 'x',
        'status' => 'open',
        'expires_at' => now()->addDay()->endOfDay(),
        'alerts_sent_at' => now(),
    ]);
    $internship->programs()->sync([$f['it']->id]);
    $internship->requirementsList()->create(['requirement_name' => 'Laravel Framework', 'required_level' => ProficiencyLevel::Beginner]);
    $internship->applications()->create(['student_id' => $f['matching']->student->id, 'status' => 'applied']);

    $this->artisan('internships:remind-deadlines')->assertSuccessful();
    expect(AppNotification::where('type', 'internship_deadline')->where('user_id', $f['matching']->id)->exists())->toBeFalse()
        ->and(AppNotification::where('type', 'internship_deadline')->where('user_id', $f['employerUser']->id)->first()->message)
        ->toContain('1 application(s)');

    $this->actingAs($f['employerUser'])->put(route('employer.internships.update', $internship), postingPayload([
        'program_ids' => [$f['it']->id],
        'expires_at' => now()->addDays(14)->format('Y-m-d'),
    ]))->assertSessionHasNoErrors();

    expect($internship->fresh()->deadline_reminded_at)->toBeNull();
});

function unsentPosting(array $f, array $overrides = []): Internship
{
    $internship = Internship::create(array_merge([
        'employer_id' => $f['employer']->id,
        'title' => 'Retry Role',
        'description' => 'x',
        'status' => 'open',
    ], $overrides));
    $internship->requirementsList()->create(['requirement_name' => 'Laravel Framework', 'required_level' => ProficiencyLevel::Beginner]);

    return $internship;
}

it('leaves a posting retryable when sending alerts fails partway', function () {
    $f = alertsFixture();
    $internship = unsentPosting($f);

    $this->mock(NotificationService::class, fn ($mock) => $mock->shouldReceive('send')->andThrow(new RuntimeException('Mail down')));

    expect(fn () => app(InternshipAlertService::class)->sendNewPostingAlerts($internship))->toThrow(RuntimeException::class);
    expect($internship->fresh()->alerts_sent_at)->toBeNull();
});

it('resumes alerts without notifying the same student twice', function () {
    $f = alertsFixture();
    $internship = unsentPosting($f);
    AppNotification::create([
        'user_id' => $f['matching']->id,
        'type' => 'internship_alert',
        'title' => 'Earlier attempt',
        'message' => 'x',
        'data' => ['internship_id' => $internship->id],
    ]);

    expect(app(InternshipAlertService::class)->sendNewPostingAlerts($internship))->toBe(1)
        ->and(AppNotification::where('type', 'internship_alert')->where('user_id', $f['matching']->id)->count())->toBe(1)
        ->and(AppNotification::where('type', 'internship_alert')->where('user_id', $f['otherProgram']->id)->count())->toBe(1)
        ->and($internship->fresh()->alerts_sent_at)->not->toBeNull();
});

it('keeps a deadline reminder retryable when sending fails partway', function () {
    $f = alertsFixture();
    $internship = unsentPosting($f, ['expires_at' => now()->addDay()->endOfDay(), 'alerts_sent_at' => now()]);

    $this->mock(NotificationService::class, fn ($mock) => $mock->shouldReceive('send')->andThrow(new RuntimeException('Mail down')));

    $this->artisan('internships:remind-deadlines')->assertFailed();
    expect($internship->fresh()->deadline_reminded_at)->toBeNull();
});

it('tells the employer the live application count, not deleted applications', function () {
    $f = alertsFixture();
    $internship = unsentPosting($f, ['expires_at' => now()->addDay()->endOfDay(), 'alerts_sent_at' => now()]);
    $internship->applications()->create(['student_id' => $f['matching']->student->id, 'status' => 'applied']);
    $internship->applications()->create(['student_id' => $f['otherProgram']->student->id, 'status' => 'applied'])->delete();

    $this->artisan('internships:remind-deadlines')->assertSuccessful();

    expect(AppNotification::where('type', 'internship_deadline')->where('user_id', $f['employerUser']->id)->first()->message)
        ->toContain('1 application(s)');
});
