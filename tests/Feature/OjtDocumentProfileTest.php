<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Appraisal;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Student;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\AppraisalService;
use App\Services\OjtDocumentData;
use App\Services\WeeklyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    // A Saturday: the week of April 20-24, 2026 (week 13 of an OJT that started January 28) has ended.
    $this->travelTo(CarbonImmutable::parse('2026-04-25 10:00:00', 'Asia/Manila'));
});

/** An active OJT for "Joshua D. Opsima" at Applus Labs (supervisor Maria L. Santos), designation IT Department. */
function docFixture(array $placement = [], array $student = []): array
{
    $institution = Institution::create(['name' => 'Doc University '.uniqid()]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer, 'name' => 'Applus Account']);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Applus Labs', 'contact_person' => 'Maria L. Santos']);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Joshua Dimaculangan Opsima', 'phone' => '09171234567']);
    $studentModel = Student::create([
        'user_id' => $studentUser->id, 'institution_id' => $institution->id,
        'first_name' => 'Joshua', 'middle_name' => 'Dimaculangan', 'last_name' => 'Opsima',
        'program' => 'BS Information Technology', 'year_level' => '4th Year',
        'student_id_number' => sprintf('SCC-21-%08d', random_int(1, 99999999)),
        ...$student,
    ]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Software Developer Trainee', 'description' => 'Build.', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $studentModel->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 80]);
    $placementModel = Placement::create([
        'application_id' => $application->id, 'student_id' => $studentModel->id, 'employer_id' => $employer->id,
        'internship_id' => $internship->id, 'start_date' => '2026-01-28', 'required_hours' => 486,
        'status' => Placement::STATUS_ACTIVE, 'department' => 'IT Department', 'rating_requested_at' => now(),
        ...$placement,
    ]);

    return [
        'placement' => $placementModel, 'student' => $studentModel, 'employer' => $employer,
        'studentUser' => $studentUser, 'employerUser' => $employerUser, 'coordinatorUser' => $coordinatorUser,
    ];
}

function docReport(array $overrides = []): array
{
    return [
        'week_start' => '2026-04-20',
        'challenges' => 'The client changed the screen layout twice this week.',
        'actions_taken' => 'I asked for written approval before each change.',
        'learnings' => 'Confirming requirements early saves a lot of rework.',
        ...$overrides,
    ];
}

function docLog(Placement $placement, string $date, float $hours, string $task): void
{
    $placement->attendanceLogs()->create([
        'work_date' => $date, 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60,
        'hours' => $hours, 'task_summary' => $task, 'status' => 'approved',
    ]);
}

function docHtml(JournalEntry $entry): string
{
    return view('ojt.journal-pdf', app(WeeklyReportService::class)->printData($entry->fresh()))->render();
}

it('formats the trainee name as "First M. Last Suffix" and normalizes typed names', function () {
    $f = docFixture(student: ['suffix' => 'Jr.']);

    expect(OjtDocumentData::fullName($f['student']))->toBe('Joshua D. Opsima Jr.')
        ->and(OjtDocumentData::normalizeName('joshua  d.  opsima'))->toBe('Joshua D. Opsima')
        ->and(OjtDocumentData::normalizeName('  juan dela cruz iii '))->toBe('Juan Dela Cruz III')
        ->and(OjtDocumentData::normalizeSuffix('jr'))->toBe('Jr.')
        ->and(OjtDocumentData::normalizeSuffix('iii'))->toBe('III');

    // Missing name parts fall back to the account name, so a form never prints a blank name.
    $f['student']->update(['first_name' => null]);
    expect(OjtDocumentData::fullName($f['student']->fresh()))->toBe('Joshua Dimaculangan Opsima');
});

it('blocks every form until the details it prints are complete (test 1)', function () {
    $f = docFixture(['department' => null]);
    $entry = JournalEntry::create(['placement_id' => $f['placement']->id, 'week_start' => '2026-04-13', 'week_number' => 12, 'title' => 'Week 12',
        'challenges' => 'x', 'actions_taken' => 'x', 'learnings' => 'x', 'status' => JournalEntry::STATUS_SUBMITTED]);
    $appraisal = app(AppraisalService::class)->request($f['placement'], $f['coordinatorUser'], CarbonImmutable::parse('2026-04-01'), CarbonImmutable::parse('2026-04-30'));
    $message = 'Complete your profile first: Designation (department).';

    // Student: submitting and printing are refused, with a link to fix it.
    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), docReport())->assertSessionHas('error', $message);
    expect($f['placement']->journalEntries()->count())->toBe(1);
    $this->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => collect(Appraisal::FACTORS)->map(fn ($x) => 5)->all()])->assertSessionHas('error', $message);
    expect($appraisal->fresh()->self_submitted_at)->toBeNull();
    $this->get(route('ojt.journals.print', $entry))->assertStatus(409)->assertSee($message)->assertSee('Set my OJT details');
    $this->get(route('ojt.appraisals.print', $appraisal))->assertStatus(409)->assertSee($message);
    $this->get(route('student.ojt.index'))->assertSee('Complete your profile first:')->assertSee('Designation (department)');

    // Supervisor and coordinator get the same block, worded for them.
    $this->actingAs($f['employerUser'])->get(route('ojt.journals.print', $entry))->assertStatus(409)
        ->assertSee("The trainee's details are incomplete: Designation (department).")->assertSee('Set the OJT details');
    $this->actingAs($f['coordinatorUser'])->get(route('ojt.appraisals.print', $appraisal))->assertStatus(409);

    // Final evaluation and certificate need the profile fields they print.
    $f['student']->update(['program' => null, 'student_id_number' => null]);
    Evaluation::create(['placement_id' => $f['placement']->id, 'form_version' => 2, 'scores' => [], 'average_score' => 5, 'submitted_at' => now(), 'department_assigned' => 'IT Department']);
    $this->actingAs($f['studentUser'])->get(route('ojt.evaluations.print', $f['placement']))->assertStatus(409)
        ->assertSee('Complete your profile first: Student ID, Course.')->assertSee('Go to my profile');
    $f['placement']->update(['status' => Placement::STATUS_COMPLETED, 'cleared_at' => now(), 'certificate_number' => 'OJT-TEST-9']);
    $this->get(route('ojt.certificate', $f['placement']))->assertStatus(409)->assertSee('Course');

    // Once filled in, they print.
    $f['student']->update(['program' => 'BS Information Technology', 'student_id_number' => 'SCC-21-00000001']);
    $f['placement']->update(['department' => 'IT Department']);
    $this->get(route('ojt.journals.print', $entry))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('ojt.evaluations.print', $f['placement']))->assertOk();
    $this->get(route('ojt.certificate', $f['placement']))->assertOk();
});

it('uses a changed designation on new reports and keeps submitted ones (tests 2 and 5)', function () {
    $f = docFixture();
    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-04-13']))->assertSessionHas('success');
    $first = $f['placement']->journalEntries()->sole();
    expect($first->header_snapshot['designation'])->toBe('IT Department');

    // The student picks a new designation from the admin's list: logged, and used by the next report's form.
    $this->patch(route('student.ojt.details.update', $f['placement']), ['department' => 'Finance Department'])->assertSessionHas('success');
    $this->patch(route('student.ojt.details.update', $f['placement']), ['department' => 'Made Up Department'])->assertSessionHasErrors('department');
    expect(SystemLog::where('action', 'ojt_document_default_changed')->latest('id')->first()->details)
        ->toMatchArray(['field' => 'department', 'old' => 'IT Department', 'new' => 'Finance Department']);
    $this->get(route('student.ojt.index', ['tab' => 'weekly-journal']))->assertSee('data-hf="designation" data-default="Finance Department"', false);

    $this->post(route('student.ojt.journals.store', $f['placement']), docReport())->assertSessionHas('success');
    $second = $f['placement']->journalEntries()->where('week_number', 13)->sole();
    expect($second->header_snapshot['designation'])->toBe('Finance Department')
        ->and($first->fresh()->header_snapshot['designation'])->toBe('IT Department');

    // Certified, then the profile changes: the certified report keeps what it was submitted with.
    $this->actingAs($f['employerUser'])->patch(route('employer.interns.journals.review', [$f['placement'], $first]), ['supervisor_comment' => 'OK'])->assertSessionHas('success');
    $f['student']->update(['first_name' => 'Josh', 'program' => 'BS Computer Science']);
    $f['employer']->update(['company_name' => 'Renamed Corp', 'contact_person' => 'New Person']);
    $html = docHtml($first);
    expect($html)->toContain('Joshua D. Opsima')->toContain('Applus Labs')->toContain('Maria L. Santos')->toContain('IT Department')
        ->not->toContain('Renamed Corp')->not->toContain('Josh D. Opsima');
});

it('changes the supervisor name on one report only, unless "Also update" is ticked (tests 3 and 4)', function () {
    $f = docFixture();
    $this->actingAs($f['studentUser']);

    // Edited on this report only: Title Case, recorded with who and when; the OJT default is unchanged.
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['header' => ['supervisor_name' => '  ian   m.  dela-cruz ']]))->assertSessionHas('success');
    $edited = $f['placement']->journalEntries()->sole();
    expect($edited->header_snapshot['supervisor_name'])->toBe('Ian M. Dela-Cruz')
        ->and($edited->header_overrides['supervisor_name'])->toMatchArray(['value' => 'Ian M. Dela-Cruz', 'default' => 'Maria L. Santos', 'by_user_id' => $f['studentUser']->id])
        ->and($f['placement']->fresh()->supervisor_name)->toBeNull();

    // The next report starts from the default again ("Reset to profile value" sends nothing for the field).
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-04-13']))->assertSessionHas('success');
    $default = $f['placement']->journalEntries()->where('week_number', 12)->sole();
    expect($default->header_snapshot['supervisor_name'])->toBe('Maria L. Santos')->and($default->header_overrides)->toBeNull();

    // Sending the default value back is not a change either.
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-04-06', 'header' => ['supervisor_name' => 'Maria L. Santos']]))->assertSessionHas('success');
    expect($f['placement']->journalEntries()->where('week_number', 11)->sole()->header_overrides)->toBeNull();

    // Ticked: it becomes the OJT's default (logged) instead of a one-report change.
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-03-30', 'header' => ['supervisor_name' => 'Ian M. Dela Cruz'], 'save_default' => ['supervisor_name']]))->assertSessionHas('success');
    expect($f['placement']->fresh()->supervisor_name)->toBe('Ian M. Dela Cruz')
        ->and($f['placement']->journalEntries()->where('week_number', 10)->sole()->header_overrides)->toBeNull()
        ->and(SystemLog::where('action', 'ojt_document_default_changed')->latest('id')->first()->details)->toMatchArray(['field' => 'supervisor_name', 'new' => 'Ian M. Dela Cruz']);
    // The earlier reports keep their own supervisor.
    expect($edited->fresh()->header_snapshot['supervisor_name'])->toBe('Ian M. Dela-Cruz')
        ->and($default->fresh()->header_snapshot['supervisor_name'])->toBe('Maria L. Santos');

    // Name rules: letters, spaces, periods, hyphens; 2-80 characters. Designation only from the list.
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-03-23', 'header' => ['supervisor_name' => 'J0hn']]))->assertSessionHasErrors('header.supervisor_name');
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-03-23', 'header' => ['designation' => 'Typed Department']]))->assertSessionHasErrors('header.designation');
});

it('accepts an inclusive-dates change only inside that week (Monday to Sunday)', function () {
    $f = docFixture();
    $this->actingAs($f['studentUser']);

    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['header' => ['inclusive_dates' => 'April 27 - 28, 2026']]))->assertSessionHasErrors('header.inclusive_dates');
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['header' => ['inclusive_dates' => 'april 21 - 24, 2026', 'period_of_evaluation' => 'finals (week 13)']]))->assertSessionHas('success');
    $entry = $f['placement']->journalEntries()->sole();
    expect($entry->header_snapshot['inclusive_dates'])->toBe('April 21 - 24, 2026')
        ->and($entry->header_snapshot['period_of_evaluation'])->toBe('FINALS (WEEK 13)')
        ->and(array_keys($entry->header_overrides))->toBe(['period_of_evaluation', 'inclusive_dates']);

    expect(OjtDocumentData::normalizeInclusiveDates('Apr 30 - May 1, 2026', CarbonImmutable::parse('2026-04-27')))->toBe('April 30 - May 1, 2026')
        ->and(OjtDocumentData::normalizeInclusiveDates('December 29, 2025 - January 2, 2026', CarbonImmutable::parse('2025-12-29')))->toBe('December 29, 2025 - January 2, 2026')
        // A weekend day the student worked can be reported; the next week cannot.
        ->and(OjtDocumentData::normalizeInclusiveDates('April 25, 2026', CarbonImmutable::parse('2026-04-20')))->toBe('April 25, 2026')
        ->and(OjtDocumentData::normalizeInclusiveDates('April 27, 2026', CarbonImmutable::parse('2026-04-20')))->toBeNull()
        // Never before the OJT start.
        ->and(OjtDocumentData::normalizeInclusiveDates('January 26 - 30, 2026', CarbonImmutable::parse('2026-01-28'), CarbonImmutable::parse('2026-01-28')))->toBeNull();
});

it('freezes the trainee details on monthly and final evaluations when they are submitted (test 5)', function () {
    $f = docFixture();
    $appraisal = app(AppraisalService::class)->request($f['placement'], $f['coordinatorUser'], CarbonImmutable::parse('2026-04-01'), CarbonImmutable::parse('2026-04-30'));
    $this->actingAs($f['studentUser'])->post(route('student.ojt.appraisals.self', $appraisal), ['scores' => collect(Appraisal::FACTORS)->map(fn ($x) => 5)->all()])->assertSessionHas('success');

    $this->actingAs($f['employerUser'])->post(route('employer.interns.evaluation.store', $f['placement']), [
        'department_assigned' => 'IT Department', 'field_of_training' => 'Software Developer Trainee', 'supervisor_designation' => 'Lead',
        'work_scores' => collect(Evaluation::WORK_FACTORS)->map(fn ($x) => $x['points'])->all(),
        'trait_scores' => collect(Evaluation::TRAIT_FACTORS)->map(fn ($x) => $x['points'])->all(),
        'comments' => 'Reliable.',
    ])->assertSessionHas('success');

    $f['student']->update(['first_name' => 'Josh']);
    $f['placement']->update(['department' => 'Finance Department']);

    expect($appraisal->fresh()->header_snapshot)->toMatchArray(['full_name' => 'Joshua D. Opsima', 'designation' => 'IT Department', 'company' => 'Applus Labs'])
        ->and($f['placement']->evaluation()->first()->header_snapshot)->toMatchArray(['full_name' => 'Joshua D. Opsima', 'contact_number' => '09171234567']);
    // The final evaluation's department comes from the same list.
    $this->post(route('employer.interns.evaluation.store', $f['placement']), ['department_assigned' => 'Typed Dept'])->assertSessionHasErrors('department_assigned');
});

it('validates the student profile: names, PH mobile, unique email and ID, locked ID, logged changes (test 6)', function () {
    $f = docFixture(student: ['student_id_number' => null]);
    $other = docFixture();
    $this->actingAs($f['studentUser']);

    // Names are trimmed and title-cased; letters, spaces, hyphens and periods only.
    $this->putJson(route('student.profile.update'), [
        'profile_section' => 'personal', 'privacy_consent' => '1', 'gender' => 'Male', 'date_of_birth' => '2003-01-01',
        'first_name' => 'joshua  paolo', 'middle_name' => 'dimaculangan', 'last_name' => '  opsima ', 'suffix' => 'jr',
    ])->assertOk();
    $student = $f['student']->fresh();
    expect([$student->first_name, $student->middle_name, $student->last_name, $student->suffix])->toBe(['Joshua Paolo', 'Dimaculangan', 'Opsima', 'Jr.'])
        ->and(OjtDocumentData::fullName($student))->toBe('Joshua Paolo D. Opsima Jr.')
        ->and(SystemLog::where('action', 'student_identity_changed')->latest('id')->first()->details)
        ->toMatchArray(['field' => 'name', 'old' => 'Joshua Dimaculangan Opsima', 'new' => 'Joshua Paolo Dimaculangan Opsima Jr.']);
    $this->putJson(route('student.profile.update'), ['profile_section' => 'personal', 'privacy_consent' => '1', 'gender' => 'Male', 'date_of_birth' => '2003-01-01',
        'first_name' => 'J0shua', 'last_name' => 'O', 'suffix' => 'Esq.'])
        ->assertUnprocessable()->assertJsonValidationErrors(['first_name', 'last_name', 'suffix']);

    // Philippine mobile number; email unique.
    $contact = ['profile_section' => 'contact', 'privacy_consent' => '1', 'email' => $f['studentUser']->email, 'address' => '1 Street', 'city' => 'Cebu City', 'province' => 'Cebu',
        'zip_code' => '6000', 'emergency_contact_name' => 'Ana Opsima', 'emergency_contact_relationship' => 'Mother', 'emergency_contact_address' => '1 Street', 'emergency_contact_phone' => '09181112222'];
    $this->putJson(route('student.profile.update'), [...$contact, 'phone' => '12345'])->assertUnprocessable()->assertJsonValidationErrors('phone');
    $this->putJson(route('student.profile.update'), [...$contact, 'phone' => '0817 123 4567'])->assertUnprocessable()->assertJsonValidationErrors('phone');
    $this->putJson(route('student.profile.update'), [...$contact, 'phone' => '+63 917 123 4567'])->assertOk();
    $this->putJson(route('student.profile.update'), [...$contact, 'phone' => '09171234567', 'email' => $other['studentUser']->email])->assertUnprocessable()->assertJsonValidationErrors('email');

    // Student ID: unique; once saved in the right format, the student cannot change it.
    $academic = ['profile_section' => 'academic', 'privacy_consent' => '1', ...collect(bioDataDefaults())->only(['department', 'year_level', 'expected_graduation', 'elementary_school', 'elementary_year_graduated', 'high_school', 'high_school_year_graduated'])->all(), 'program' => 'BS Information Technology'];
    $this->putJson(route('student.profile.update'), [...$academic, 'student_number' => $other['student']->student_id_number])->assertUnprocessable()->assertJsonValidationErrors('student_number');
    $this->putJson(route('student.profile.update'), [...$academic, 'student_number' => 'SCC-22-00012345'])->assertOk();
    $this->actingAs($f['studentUser']->fresh()); // a new request: the saved ID is now on record
    $this->putJson(route('student.profile.update'), [...$academic, 'student_number' => 'SCC-22-00099999'])->assertUnprocessable()
        ->assertJsonValidationErrors(['student_number' => "can't be changed after it is saved"]);
    expect(SystemLog::where('action', 'student_identity_changed')->get()->pluck('details.field')->all())->toContain('student_id_number');
});

it('rejects an OJT end date that is not after the start date, and days over 12 hours (test 6)', function () {
    $f = docFixture();
    $this->actingAs($f['employerUser'])->put(route('employer.interns.schedule', $f['placement']), ['start_date' => '2026-01-28', 'end_date' => '2026-01-28'])->assertSessionHasErrors('end_date');
    $this->put(route('employer.interns.schedule', $f['placement']), ['start_date' => '2026-01-28', 'end_date' => '2026-05-29', 'department' => 'Finance Department', 'supervisor_name' => 'maria l. santos'])->assertSessionHas('success');
    expect($f['placement']->fresh()->only(['department', 'supervisor_name']))->toBe(['department' => 'Finance Department', 'supervisor_name' => 'Maria L. Santos']);
    $this->put(route('employer.interns.schedule', $f['placement']), ['start_date' => '2026-01-28', 'department' => 'Typed Dept'])->assertSessionHasErrors('department');

    $this->actingAs($f['studentUser'])->post(route('student.ojt.attendance.store', $f['placement']), [
        'work_date' => '2026-04-24', 'time_in' => '06:00', 'time_out' => '19:30', 'break_minutes' => 60, 'task_summary' => 'Long day',
    ])->assertSessionHasErrors(['time_out' => 'A single day must be between a few minutes and 12 hours after breaks.']);
});

it('prints the official layout with no placeholder text and a filled Designation (test 7)', function () {
    $f = docFixture();
    docLog($f['placement'], '2026-04-20', 8.05, 'Continued work on the remaining features of the system.');
    docLog($f['placement'], '2026-04-24', 5.7, 'Awarding of the Certificate of Completion');
    $this->actingAs($f['studentUser']);

    // "DONE" is not a report: each section needs at least 20 characters.
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['challenges' => 'DONE', 'actions_taken' => 'DONE', 'learnings' => 'DONE']))
        ->assertSessionHasErrors(['challenges', 'actions_taken', 'learnings']);
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport())->assertSessionHas('success');
    $entry = $f['placement']->journalEntries()->sole();

    $html = docHtml($entry);
    expect($html)->toContain('COLLEGE OF INFORMATION TECHNOLOGY')
        ->toContain('Week 13 – Activity Narrative Report')->toContain('OJT / PRACTICUM TRAINEE')
        ->toContain('Joshua D. Opsima')->toContain('Applus Labs')->toContain('Maria L. Santos')
        ->toContain('FINALS (Week 13)')->toContain('April 20 - 24, 2026')
        ->toContain('<td class="value">IT Department</td>')
        ->toContain('DIFFICULTIES ENCOUNTERED')->toContain('ACTIONS TAKEN')->toContain('LEARNING INSIGHTS')
        ->toContain('8 hrs and 3 mins')->toContain('5 hrs and 42 mins')
        ->toContain('Certified True and Correct')
        ->not->toContain('DONE');
    // Exactly five weekday rows; days without a time record print blank, not "-".
    expect(substr_count($html, '<td class="center nowrap">'))->toBe(5)
        ->and(substr_count($html, '<td class="center nowrap"></td>'))->toBe(3)
        ->and($html)->not->toContain('<td class="center nowrap">-</td>');
    // Not certified yet: no name under the signature line.
    expect($html)->toContain('<div class="sign-name"></div>');
});

it('prints the report on one Letter page, shrinking long text to fit (test 8)', function () {
    $f = docFixture();
    foreach (['2026-04-20', '2026-04-21', '2026-04-22', '2026-04-23', '2026-04-24'] as $day) {
        docLog($f['placement'], $day, 8, str_repeat('Built and tested the attendance module screens. ', 4));
    }
    $pages = function (JournalEntry $entry): array {
        $pdf = Pdf::loadView('ojt.journal-pdf', app(WeeklyReportService::class)->printData($entry->fresh()))->setPaper('letter', 'portrait')->output();

        return [preg_match_all('#/Type\s*/Page[^s]#', $pdf), (bool) preg_match('#/MediaBox\s*\[\s*0(\.0+)?\s+0(\.0+)?\s+612(\.0+)?\s+792(\.0+)?\s*\]#', $pdf)];
    };

    $this->actingAs($f['studentUser'])->post(route('student.ojt.journals.store', $f['placement']), docReport())->assertSessionHas('success');
    expect($pages($f['placement']->journalEntries()->sole()))->toBe([1, true]);

    $long = str_repeat('The team reviewed every screen with the client and I fixed the issues they raised. ', 9);
    $this->post(route('student.ojt.journals.store', $f['placement']), docReport(['week_start' => '2026-04-13', 'challenges' => $long, 'actions_taken' => $long, 'learnings' => $long]))->assertSessionHas('success');
    $longEntry = $f['placement']->journalEntries()->where('week_number', 12)->sole();
    expect(app(WeeklyReportService::class)->printData($longEntry)['fontSize'])->toBeLessThan(10.5)
        ->and($pages($longEntry))->toBe([1, true]);

    // The route serves it as a PDF.
    $this->get(route('ojt.journals.print', $longEntry))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('lets the admin manage the Designation list', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    expect(AcademicSetting::current()->ojtDepartments())->toBe(AcademicSetting::DEFAULT_OJT_DEPARTMENTS);

    $payload = collect(AcademicSetting::current()->toArray())->except(['id', 'created_at', 'updated_at'])->all();
    $this->actingAs($admin)->post(route('admin.academics.settings.update'), [...$payload, 'ojt_departments_text' => "IT Department\n  Accounting   Office \n\nIT Department"])
        ->assertSessionHasErrors('ojt_departments.0');
    $this->post(route('admin.academics.settings.update'), [...$payload, 'ojt_departments_text' => "IT Department\n  Accounting   Office \n"])->assertSessionHas('success');
    expect(AcademicSetting::current()->ojtDepartments())->toBe(['IT Department', 'Accounting Office'])
        ->and(app(OjtDocumentData::class)->designations('Old Spelling'))->toBe(['IT Department', 'Accounting Office', 'Old Spelling']);
});
