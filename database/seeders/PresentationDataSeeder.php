<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillLevel;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Appraisal;
use App\Models\AttendanceLog;
use App\Models\CompanyPhoto;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipRequirement;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\StudentCompetencyScore;
use App\Models\StudentSkill;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\CatalogName;
use Carbon\CarbonImmutable;
use Database\Seeders\Presentation\Data;
use Database\Seeders\Presentation\Images;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Sample data for a presentation: 10 students (2 per department, one per coordinator), 10 IT companies with
 * their HR accounts, supervisors, logos and photos, 9 more coordinators (2 per department), competency scores,
 * 5 certificates per student, placements at every OJT stage with time records, weekly reports, monthly
 * appraisals and final evaluations, and a picture for everyone who has none.
 *
 * Safe to run again and on a database that already has data: every record is found by its email, student ID,
 * company, department or date, and an existing record is never changed or deleted. Not for production.
 *
 *   php artisan db:seed --class=PresentationDataSeeder
 */
class PresentationDataSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $summary = [];

    private ?AcademicSetting $settings = null;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Production: the presentation data uses the password "password", so it was not created.');

            return;
        }
        if (! function_exists('imagecreatetruecolor')) {
            $this->command?->error('The PHP GD extension is needed to draw the pictures. Enable extension=gd in php.ini.');

            return;
        }
        if (Program::query()->count() === 0) {
            $this->call(AcademicCatalogSeeder::class);
        }

        $this->settings = AcademicSetting::current();
        $institution = $this->institution();
        $programs = Program::query()->get()->keyBy('code');
        $admin = User::query()->where('role', UserRole::Admin->value)->orderBy('id')->first();

        $coordinators = $this->coordinators($institution, $programs);
        $companies = $this->companies($admin);
        $students = $this->students($institution, $programs, $coordinators, $companies);

        foreach ($students as $row) {
            $this->scores($row);
            $this->skills($row);
            $this->certificates($row);
            if ($row['company']) {
                $this->ojt($row); // the 10 extra students have no employer, so no OJT
            }
        }
        $this->companyPictures($companies);
        $this->avatars();
        $this->storageLink();
        $this->report($coordinators, $companies, $students);
    }

    // ------------------------------------------------------------------ people

    private function institution(): Institution
    {
        $institution = Institution::query()->orderBy('id')->first()
            ?? Institution::create(['name' => "St. Cecilia's College-Cebu, Inc.", 'address' => 'N. Bacalso S National Highway, Ward II, Poblacion, Minglanilla, Cebu']);
        if ($institution->verified_at === null) {
            $institution->forceFill(['verified_at' => now()])->save();
        }

        return $institution;
    }

    private function user(string $name, UserRole $role, ?string $email = null): User
    {
        $email ??= Data::email($name);
        $user = User::query()->where('email', $email)->first();
        if (! $user && ($legacy = User::query()->where('email', Data::legacyEmail($name))->first())) {
            // Seeded earlier with an @example.com address: the same account, now with a realistic one.
            $legacy->forceFill(['email' => $email])->save();
            $user = $legacy;
        }
        $user ??= User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Data::PASSWORD,
            'role' => $role,
            'phone' => Data::phone($name),
            'is_active' => true,
        ]);
        if ($user->wasRecentlyCreated) {
            $this->summary['new_'.$role->value] = ($this->summary['new_'.$role->value] ?? 0) + 1;
        }
        // Demo accounts sign in right away (no email code, no forced password change). Their addresses look real and
        // may belong to real people, so the system's notification emails to them are switched off.
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now(), 'must_change_password' => false, 'email_notifications' => false, 'demo_account' => true])->save();

        return $user;
    }

    /**
     * Up to two coordinators per department: the ones already there stay, and the new ones fill the free places.
     *
     * @return array<string, list<Coordinator>> department code => coordinators, oldest first
     */
    private function coordinators(Institution $institution, $programs): array
    {
        foreach (Data::coordinators() as $row) {
            $program = $programs[$row['program']] ?? null;
            if (! $program) {
                continue;
            }
            $user = $this->user($row['name'], UserRole::Coordinator);
            $existing = Coordinator::query()->where('user_id', $user->id)->first();
            if (! $existing && ! Coordinator::departmentHasRoom($program->id)) {
                continue; // the department already has 2 coordinators
            }
            Coordinator::query()->firstOrCreate(['user_id' => $user->id], [
                'institution_id' => $institution->id,
                'program_id' => $program->id,
                'office_address' => 'OJT Office, '.($row['program'] === 'BSIT' ? 'College of Information Technology' : $program->name).", St. Cecilia's College-Cebu",
            ]);
        }

        $byProgram = [];
        foreach ($programs as $code => $program) {
            $byProgram[$code] = Coordinator::query()->with('user')->where('program_id', $program->id)->orderBy('id')->get()->all();
        }

        return $byProgram;
    }

    /** @return list<array{employer: Employer, hr: User, supervisor: User, internship: Internship, def: array}> */
    private function companies(?User $admin): array
    {
        $rows = [];
        foreach (Data::companies() as $index => $def) {
            $hr = $this->user($def['hr'], UserRole::Employer);
            $slug = Data::slug($def['company']);
            $employer = Employer::query()->firstOrCreate(['user_id' => $hr->id], [
                'company_name' => $def['company'],
                'industry' => $def['industry'],
                'company_size' => $def['size'],
                'description' => $def['company'].' builds software and runs IT services for businesses in Metro Cebu. Our interns work beside our engineers, support staff and business teams on real client projects.',
                'website' => 'https://www.'.$slug.'.example.com',
                'address' => $def['address'],
                'contact_person' => $def['hr'],
                'position' => 'HR Manager',
                'verified_at' => now(),
                'verified_by_user_id' => $admin?->id,
                'moa_signed_on' => now()->subMonths(8)->toDateString(),
                'moa_expires_on' => now()->addMonths(16)->toDateString(),
            ]);

            $supervisor = $this->user($def['supervisor'], UserRole::Supervisor);
            Supervisor::query()->firstOrCreate(['user_id' => $supervisor->id], [
                'employer_id' => $employer->id,
                'position' => $def['supervisor_position'],
                'created_by_user_id' => $hr->id,
            ]);

            $internship = Internship::query()->firstOrCreate(['employer_id' => $employer->id, 'title' => $def['role']], [
                'description' => $def['role'].' at '.$def['company'].'. You will learn how a Cebu IT company works day to day and get guidance from a dedicated supervisor.',
                'responsibilities' => "Support the team with daily tasks\nPrepare simple reports and documentation\nJoin team meetings and learn the tools we use",
                'qualifications' => '3rd or 4th year student, good communication, willing to learn.',
                'slots' => 2,
                'duration' => '400 to 600 hours',
                'allowance' => 4000 + ($index % 4) * 500,
                'location' => $this->city($def['address']),
                'status' => 'open',
            ]);

            $rows[] = ['employer' => $employer, 'hr' => $hr, 'supervisor' => $supervisor, 'internship' => $internship, 'def' => $def];
        }

        return $rows;
    }

    /** "Cebu IT Park, Apas, Lahug, Cebu City" => "Cebu City"; "Tabunok, Talisay City, Cebu" => "Talisay City". */
    private function city(string $address): string
    {
        $parts = array_map('trim', explode(',', $address));
        $last = end($parts);

        return $last === 'Cebu' && count($parts) > 1 ? $parts[count($parts) - 2] : $last;
    }

    /** @return list<array<string, mixed>> */
    private function students(Institution $institution, $programs, array $coordinators, array $companies): array
    {
        $departments = Data::departments();
        $localities = Data::localities();
        $schools = Data::schools();
        $middleNames = ['Dacera', 'Aguilar', 'Rosal', 'Navarro', 'Tampus', 'Cortes', 'Baguio', 'Alcantara', 'Jumao-as', 'Padilla'];
        $rows = [];

        $all = [...Data::students(), ...Data::freeStudents(), ...Data::bulkStudents()];
        foreach ($all as $index => $def) {
            $program = $programs[$def['program']];
            $department = $departments[$def['program']];
            $user = $this->user($def['name'], UserRole::Student);
            [$first, $last] = [explode(' ', $def['name'])[0], explode(' ', $def['name'])[1]];
            [$city, $barangay] = $localities[$index % count($localities)];
            [$elementary, $highSchool] = $schools[$index % count($schools)];
            $position = count(array_filter($all, fn ($other, $i) => $i < $index && $other['program'] === $def['program'], ARRAY_FILTER_USE_BOTH));
            $list = $coordinators[$def['program']] ?? [];
            $coordinator = $list ? $list[$position % count($list)] : null;
            $graduationYear = $def['year'] === '4th Year' ? 2027 : 2028;

            $student = Student::query()->firstOrCreate(['user_id' => $user->id], [
                'institution_id' => $institution->id,
                'student_id_number' => sprintf('SCC-23-%08d', 24001 + $index),
                'student_id_locked' => true,
                'program_id' => $program->id,
                'program' => $program->display_name,
                'department' => $department['college'],
                'year_level' => $def['year'],
                'section' => $def['section'],
                'school_year' => '2026-2027',
                'semester' => 'first',
                'enrollment_status' => 'Regular',
                'gwa' => number_format(1.4 + (abs(crc32($def['name'])) % 80) / 100, 2),
                'expected_graduation' => $graduationYear.'-05-31',
                'first_name' => $first,
                'last_name' => $last,
                'middle_name' => $middleNames[$index % count($middleNames)],
                'gender' => $def['gender'],
                'date_of_birth' => (2002 + $index % 3).'-'.sprintf('%02d', 1 + ($index * 5) % 12).'-'.sprintf('%02d', 3 + ($index * 7) % 24),
                'place_of_birth' => $city,
                'citizenship' => 'Filipino',
                'languages' => 'Cebuano, Filipino, English',
                'address' => $barangay.', '.$city,
                'city' => $city,
                'province' => 'Cebu',
                'zip_code' => '60'.sprintf('%02d', 1 + ($index * 4) % 47),
                'about_me' => 'A '.$def['year'].' '.$program->code.' student who enjoys learning on the job. I want to use my OJT to practise '.mb_strtolower($department['field']).' and grow as a professional.',
                'emergency_contact_name' => 'Maria '.$last,
                'emergency_contact_relationship' => 'Mother',
                'emergency_contact_address' => $barangay.', '.$city,
                'emergency_contact_phone' => Data::phone('Maria '.$last),
                'elementary_school' => $elementary,
                'elementary_year_graduated' => '2015-2016',
                'high_school' => $highSchool,
                'high_school_year_graduated' => '2021-2022',
                'privacy_consent' => true,
                'privacy_consent_at' => now(),
                'discoverable' => true,
                'major_subjects_passed' => true,
                'profile_verified_at' => now(),
                'profile_verified_by_user_id' => $coordinator?->user_id,
            ]);
            if ($student->wasRecentlyCreated) {
                $this->summary['new_student_profiles'] = ($this->summary['new_student_profiles'] ?? 0) + 1;
            }

            $this->portrait($student, $user, $def);
            $rows[] = ['def' => $def, 'index' => $index, 'user' => $user, 'student' => $student, 'program' => $program, 'dept' => $department,
                'coordinator' => $coordinator, 'company' => $companies[$index] ?? null];
        }

        return $rows;
    }

    /**
     * The student's profile picture: an illustrated portrait. It is written to the file this seeder always uses for the
     * student, so running again rewrites the same file; a picture the student uploaded themselves is never replaced.
     */
    private function portrait(Student $student, User $user, array $def): void
    {
        $disk = Storage::disk('public');
        $file = 'avatars/student/'.$user->id.'.png';
        $current = $student->profile_picture;
        if (filled($current) && $current !== $file && $disk->exists($current)) {
            return; // their own picture
        }
        $disk->put($file, Images::portrait($def['gender'], abs(crc32($def['name']))));
        if ($current !== $file) {
            $student->forceFill(['profile_picture' => $file])->save();
        }
    }

    // ------------------------------------------------------------------ competencies, skills, certificates

    /** 0-100 % per competency, within the range of each, with this student's strengths and weak spots. */
    private function scores(array $row): void
    {
        $student = $row['student'];
        $index = 0;
        foreach (StudentCompetencyScore::SKILLS as $skill => [$min, $max]) {
            $score = $min + abs(crc32($row['def']['name'].$skill)) % ($max - $min + 1);
            $score += in_array($index, $row['def']['strengths'], true) ? 6 : 0;
            $score -= in_array($index, $row['def']['weak'], true) ? 7 : 0;
            StudentCompetencyScore::query()->updateOrCreate(
                ['student_id' => $student->id, 'skill_name' => $skill],
                ['score' => max($min, min($max, $score))],
            );
            $index++;
        }
        $this->summary['competency_scores'] = StudentCompetencyScore::query()->count();
    }

    private function skills(array $row): void
    {
        $student = $row['student'];
        foreach ($row['def']['skills'] ?? $row['dept']['skills'] as $i => $name) {
            $tag = SkillTag::query()->firstOrCreate(['name_normalized' => CatalogName::normalized($name)], ['name' => $name]);
            StudentSkill::query()->firstOrCreate(['student_id' => $student->id, 'skill_tag_id' => $tag->id], [
                'level' => [SkillLevel::Advanced, SkillLevel::Intermediate, SkillLevel::Intermediate, SkillLevel::Beginner][($i + $row['index']) % 4],
            ]);
        }
    }

    /** Five certificates per student, each with a drawn certificate image on the public disk. */
    private function certificates(array $row): void
    {
        $student = $row['student'];
        $disk = Storage::disk('public');
        foreach ($row['def']['certs'] ?? $row['dept']['certificates'] as $i => [$title, $type, $issuer, $skillType, $level, $learned]) {
            $obtained = CarbonImmutable::now()->subDays(20 + ($i * 120 + $row['index'] * 9) % 680);
            $path = 'certificates/'.$student->id.'/'.Data::slug($title).'.png';
            if (! $disk->exists($path)) {
                $disk->put($path, Images::certificate($title, $row['def']['name'], $issuer, $obtained->format('F j, Y'), $row['index'] + $i));
            }

            $certificate = StudentCompetency::query()->firstOrCreate(
                ['student_id' => $student->id, 'name' => $title, 'certificate_type' => $type],
                [
                    'category' => $type->legacyCategory(),
                    'skill_type' => $skillType,
                    'proficiency_level' => $level,
                    'issuing_organization' => $issuer,
                    'obtained_at' => $obtained->toDateString(),
                    'description' => 'Completed '.mb_strtolower($title).' with '.$issuer.'.',
                    'evidence_path' => $path,
                    'evidence_name' => Data::slug($title).'.png',
                    'verification_status' => 'verified',
                    'reviewed_at' => $obtained->addDays(6),
                    'reviewed_by' => $row['coordinator']?->id, // the coordinator row
                ],
            );
            if ($certificate->wasRecentlyCreated) {
                foreach ($learned as $skill) {
                    $certificate->learnedSkills()->create(['name' => $skill]);
                }
            }
        }
        $student->calculateProfileCompletion();
    }

    // ------------------------------------------------------------------ the OJT

    /** Application, placement and, by stage, time records, weekly reports, monthly appraisals and the final evaluation. */
    private function ojt(array $row): void
    {
        $student = $row['student'];
        $company = $row['company'];
        $coordinatorUser = $row['coordinator']?->user;
        $supervisor = $company['supervisor'];
        $stage = $row['def']['ojt'];
        $monday = CarbonImmutable::now()->startOfWeek();
        $required = $row['program']->required_internship_hours ?: 400;
        // A finished OJT runs long enough to reach the department's required hours (about 37 hours logged a week).
        $duration = (int) ceil($required / 40 * 1.15) + 1;
        $weeksBack = ['completed_cleared' => 20, 'completed' => 20, 'pending_validation' => -1];
        $ongoingWeeks = [9, 7, 5, 4, 3];
        $start = match ($stage) {
            'ongoing' => $monday->subWeeks($ongoingWeeks[intdiv($row['index'], 2) % count($ongoingWeeks)]),
            'pending_validation' => $monday->addWeek(),
            default => $monday->subWeeks($weeksBack[$stage]),
        };
        $completed = str_starts_with($stage, 'completed');
        $end = $completed ? $start->addWeeks($duration)->subDays(3) : null; // a Friday
        $company['internship']->programs()->syncWithoutDetaching([$row['program']->id]);
        // Two skills the internship asks for, taken from what the department's students learn, so the match score means something.
        foreach (array_slice(array_merge(...array_map(fn ($certificate) => array_slice($certificate[5], 0, 1), $row['dept']['certificates'])), 0, 2) as $requirement) {
            InternshipRequirement::query()->firstOrCreate(
                ['internship_id' => $company['internship']->id, 'requirement_name' => $requirement],
                ['required_level' => ProficiencyLevel::Beginner],
            );
        }

        $application = InternshipApplication::query()->firstOrCreate(
            ['internship_id' => $company['internship']->id, 'student_id' => $student->id],
            [
                'status' => ApplicationStatus::Accepted,
                'match_percentage' => 72 + abs(crc32($row['def']['name'])) % 23,
                'applied_at' => $start->subWeeks(3),
                'cover_letter' => 'Good day! I am a '.$row['def']['year'].' student of '.$row['program']->name.'. I would like to do my OJT with '.$company['employer']->company_name.' to learn from your team.',
            ],
        );

        $placement = Placement::query()->firstOrCreate(['application_id' => $application->id], [
            'student_id' => $student->id,
            'employer_id' => $company['employer']->id,
            'internship_id' => $company['internship']->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end?->toDateString(),
            'required_hours' => $required,
            'status' => $stage === 'pending_validation' ? Placement::STATUS_PENDING_VALIDATION : Placement::STATUS_ACTIVE,
            'department' => $row['dept']['designation'],
            'supervisor_user_id' => $supervisor->id,
            'validated_at' => $stage === 'pending_validation' ? null : $start->subDays(5),
            'validated_by_user_id' => $stage === 'pending_validation' ? null : $coordinatorUser?->id,
            'validation_remarks' => $stage === 'pending_validation' ? null : 'MOA signed, internship open to the program.',
        ]);
        if ($stage === 'pending_validation') {
            return;
        }

        $this->logs($placement, $row, $supervisor);
        $this->journals($placement, $row, $supervisor);
        $this->appraisals($placement, $row, $supervisor, $coordinatorUser);

        if ($completed) {
            $this->finish($placement, $row, $supervisor, $coordinatorUser, $stage === 'completed_cleared', $application);
        } elseif ($row['index'] === 8) {
            // Angelica: her final evaluation was just requested from the company.
            $placement->forceFill([
                'rating_requested_at' => $placement->rating_requested_at ?? now()->subDays(2),
                'rating_requested_by_user_id' => $placement->rating_requested_by_user_id ?? $coordinatorUser?->id,
                'rating_due_on' => $placement->rating_due_on ?? now()->addDays(5)->toDateString(),
            ])->save();
        }
    }

    /** Weekday time records from the start to yesterday, a few days missed, the newest ones still waiting for approval. */
    private function logs(Placement $placement, array $row, User $supervisor): void
    {
        $tasks = $row['dept']['tasks'];
        $last = $placement->end_date ? $placement->end_date->toImmutable() : CarbonImmutable::now()->subDay();
        $last = $last->gt(CarbonImmutable::now()->subDay()) ? CarbonImmutable::now()->subDay() : $last;
        $pendingFrom = CarbonImmutable::now()->subDays(2)->startOfDay();
        $completed = $placement->end_date !== null;

        for ($day = $placement->start_date->toImmutable(); $day->lte($last); $day = $day->addDay()) {
            if ($day->isWeekend() || abs(crc32($placement->id.$day->toDateString())) % 17 === 0) {
                continue; // weekends and the odd absent day
            }
            $short = abs(crc32($day->toDateString().$placement->id)) % 9 === 0; // an occasional 7-hour day
            $in = '08:00';
            $out = $short ? '16:00' : '17:00';
            $approved = $completed || $day->lt($pendingFrom);
            // whereDate: a stored date may carry a time part, depending on the database.
            if (AttendanceLog::query()->where('placement_id', $placement->id)->whereDate('work_date', $day->toDateString())->exists()) {
                continue;
            }
            AttendanceLog::query()->create([
                'placement_id' => $placement->id,
                'work_date' => $day->toDateString(),
                'time_in' => $in,
                'time_out' => $out,
                'break_minutes' => 60,
                'hours' => AttendanceLog::computeHours($in, $out, 60),
                'task_summary' => $tasks[abs(crc32($day->toDateString().$row['def']['name'])) % count($tasks)],
                'status' => $approved ? AttendanceLog::STATUS_APPROVED : AttendanceLog::STATUS_PENDING,
                'reviewed_by_user_id' => $approved ? $supervisor->id : null,
                'reviewed_at' => $approved ? $day->addDay()->setTime(9, 30) : null,
            ]);
        }
    }

    /** One report for every week that has ended; the supervisor certified all but the newest of an ongoing OJT. */
    private function journals(Placement $placement, array $row, User $supervisor): void
    {
        $dept = $row['dept'];
        $today = CarbonImmutable::now();
        $monday = $placement->start_date->toImmutable()->startOfWeek();
        $endMonday = $placement->end_date?->toImmutable()->startOfWeek();
        $weeks = [];
        for ($week = $monday; $week->addDays(4)->lt($today->startOfDay()) && (! $endMonday || $week->lte($endMonday)); $week = $week->addWeek()) {
            $weeks[] = $week;
        }
        $completed = $placement->end_date !== null;

        foreach ($weeks as $i => $week) {
            $latest = $i === count($weeks) - 1;
            $certified = $completed || ! $latest;
            $pick = fn (array $list, int $shift) => $list[(abs(crc32($row['def']['name'])) + $i + $shift) % count($list)];
            $number = $placement->weekNumberFor($week);
            if (JournalEntry::query()->where('placement_id', $placement->id)->whereDate('week_start', $week->toDateString())->exists()) {
                continue;
            }
            $entry = JournalEntry::query()->create([
                'placement_id' => $placement->id,
                'week_start' => $week->toDateString(),
                'week_number' => $number,
                'title' => 'Week '.$number.' - Activity Narrative Report',
                'accomplishments' => $pick($dept['tasks'], 0).'; '.mb_strtolower($pick($dept['tasks'], 2)).'.',
                'challenges' => $pick($dept['challenges'], 0),
                'actions_taken' => $pick($dept['actions'], 0),
                'learnings' => $pick($dept['learnings'], 0),
                'status' => $certified ? JournalEntry::STATUS_ACKNOWLEDGED : JournalEntry::STATUS_SUBMITTED,
                'supervisor_comment' => $certified ? 'Good progress this week. Keep it up.' : null,
                'reviewed_by_user_id' => $certified ? $supervisor->id : null,
                'reviewed_at' => $certified ? $week->addDays(7)->setTime(10, 0) : null,
            ]);
            // A certified report keeps the daily rows it was signed with.
            if ($certified) {
                $entry->update(['daily_snapshot' => app(\App\Services\WeeklyReportService::class)->liveRows($entry)]);
            }
        }
    }

    /**
     * Monthly evaluations (Self, Supervisor, Student comment, Adviser) for every month of the OJT. Past months are
     * complete; the current month of an ongoing OJT waits at a different step for each intern.
     */
    private function appraisals(Placement $placement, array $row, User $supervisor, ?User $coordinatorUser): void
    {
        $settings = $this->settings;
        $now = CarbonImmutable::now();
        $first = $placement->start_date->toImmutable()->startOfMonth();
        $lastMonth = ($placement->end_date?->toImmutable() ?? $now)->startOfMonth();
        $waiting = ['awaiting_supervisor', 'awaiting_student', 'awaiting_adviser', 'awaiting_supervisor', 'awaiting_student'][intdiv($row['index'], 2) % 5];
        $comments = Data::comments();

        for ($month = $first; $month->lte($lastMonth); $month = $month->addMonth()) {
            $isCurrent = $placement->end_date === null && $month->equalTo($now->startOfMonth());
            $start = $month->lt($placement->start_date->toImmutable()) ? $placement->start_date->toImmutable() : $month;
            $monthEnd = $month->endOfMonth()->startOfDay();
            $end = $placement->end_date && $placement->end_date->lt($monthEnd) ? $placement->end_date->toImmutable() : $monthEnd;
            if ($isCurrent && $start->diffInDays($now) < 7) {
                continue; // too early in the month to have been requested
            }

            $existing = Appraisal::query()->where('placement_id', $placement->id)->whereDate('period_month', $month->toDateString())->first();
            if ($existing) {
                continue;
            }

            $stage = $isCurrent ? $waiting : 'complete';
            $due = $end->addDays(7);
            $submittedAt = fn (int $days) => $end->addDays($days)->setTime(14, 0)->min($now);
            $attributes = [
                'placement_id' => $placement->id,
                'period_month' => $month->toDateString(),
                'period_label' => $settings->evaluationPeriodLabelFor($month->month),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'due_on' => $due->toDateString(),
                'requested_by_user_id' => $coordinatorUser?->id,
            ];
            if ($stage !== 'awaiting_student') {
                $self = $this->factorPoints($row, 'self'.$month->format('Ym'), 4);
                $attributes += ['self_scores' => $self, 'self_total' => array_sum($self), 'self_submitted_at' => $submittedAt(1)];
            }
            if (in_array($stage, ['complete', 'awaiting_adviser'], true)) {
                $rated = $this->factorPoints($row, 'sup'.$month->format('Ym'), 0);
                $attributes += [
                    'supervisor_scores' => $rated, 'supervisor_total' => array_sum($rated),
                    'supervisor_comment' => $comments['supervisor'][($row['index'] + $month->month) % 4],
                    'supervisor_user_id' => $supervisor->id, 'supervisor_submitted_at' => $submittedAt(3),
                    'student_comment' => $comments['student'][($row['index'] + $month->month) % 4],
                    'student_comment_submitted_at' => $submittedAt(4),
                ];
            }
            if ($stage === 'complete') {
                $adviser = $this->factorPoints($row, 'adv'.$month->format('Ym'), -1);
                $attributes += ['adviser_scores' => $adviser, 'adviser_total' => array_sum($adviser), 'adviser_user_id' => $coordinatorUser?->id, 'adviser_submitted_at' => $submittedAt(6)];
            }
            Appraisal::query()->create($attributes);
        }
    }

    /**
     * Factor points for the monthly evaluation, following the student's competency scores:
     * Technical Competence 30, Quality 15, Quantity 15, Personality 10, Initiative 10, Interpersonal 10, Punctuality 10.
     *
     * @return array<string, int>
     */
    private function factorPoints(array $row, string $seed, int $bias): array
    {
        $scores = StudentCompetencyScore::query()->where('student_id', $row['student']->id)->pluck('score', 'skill_name');
        $skill = fn (int $i) => (int) ($scores[array_keys(StudentCompetencyScore::SKILLS)[$i]] ?? 80);
        $percent = [
            'technical_competence' => (int) round(($skill(0) + $skill(1) + $skill(2) + $skill(3)) / 4),
            'quality_of_work' => $skill(4),
            'quantity_of_work' => (int) round(($skill(7) + $skill(8)) / 2),
            'personality' => $skill(5),
            'initiative' => $skill(8),
            'interpersonal' => $skill(6),
            'punctuality_attendance' => $skill(7),
        ];

        $points = [];
        foreach (Appraisal::FACTORS as $key => $factor) {
            $noise = (abs(crc32($row['def']['name'].$seed.$key)) % 7) - 3; // -3..+3 %
            $value = (int) round(($percent[$key] + $bias + $noise) / 100 * $factor['points']);
            $points[$key] = max(0, min($factor['points'], $value));
        }

        return $points;
    }

    /** The final evaluation, completion and, for cleared interns, the clearance and certificate number. */
    private function finish(Placement $placement, array $row, User $supervisor, ?User $coordinatorUser, bool $cleared, InternshipApplication $application): void
    {
        $end = $placement->end_date->toImmutable();
        $scores = StudentCompetencyScore::query()->where('student_id', $row['student']->id)->pluck('score', 'skill_name');
        $skill = fn (int $i) => (int) ($scores[array_keys(StudentCompetencyScore::SKILLS)[$i]] ?? 80);
        $workBase = [
            'knowledge' => $skill(0), 'quality' => $skill(4), 'quantity' => (int) round(($skill(7) + $skill(8)) / 2),
            'punctuality' => $skill(7), 'attendance' => $skill(7),
        ];
        $work = [];
        foreach (Evaluation::WORK_FACTORS as $key => $factor) {
            $noise = (abs(crc32($row['def']['name'].'w'.$key)) % 5) - 2;
            $work[$key] = max(0, min($factor['points'], (int) round(($workBase[$key] ?? 85) / 100 * $factor['points']) + (int) round($noise / 100 * $factor['points'])));
        }
        $traitBase = [$skill(5), $skill(9), $skill(5), $skill(9), $skill(8), $skill(8), $skill(9), $skill(6), $skill(4), $skill(3)];
        $traits = [];
        foreach (array_keys(Evaluation::TRAIT_FACTORS) as $i => $key) {
            $traits[$key] = max(0, min(10, (int) round(($traitBase[$i % count($traitBase)] + (abs(crc32($row['def']['name'].'t'.$key)) % 5) - 2) / 10)));
        }
        $workTotal = array_sum($work);
        $traitTotal = array_sum($traits);

        Evaluation::query()->firstOrCreate(['placement_id' => $placement->id], [
            'evaluator_user_id' => $supervisor->id,
            'form_version' => Evaluation::FORM_SCHOOL,
            'work_scores' => $work,
            'work_total' => $workTotal,
            'trait_scores' => $traits,
            'trait_total' => $traitTotal,
            'department_assigned' => $row['dept']['designation'],
            'field_of_training' => $row['dept']['field'],
            'supervisor_designation' => $row['company']['def']['supervisor_position'],
            'scores' => [],
            'average_score' => round((($workTotal + $traitTotal) / 200) * 5, 2),
            'strengths' => 'Reliable, eager to learn and easy to work with.',
            'improvements' => 'Keep building speed and speak up earlier when stuck.',
            'comments' => $row['def']['name'].' was a dependable member of our team throughout the OJT and finished every task assigned.',
            'would_hire' => true,
            'submitted_at' => $end->addDay()->setTime(15, 0),
        ]);

        $updates = [
            'status' => Placement::STATUS_COMPLETED,
            'completed_at' => $end->addDay()->setTime(15, 0),
            'rating_requested_at' => $end->subDays(10),
            'rating_requested_by_user_id' => $coordinatorUser?->id,
            'rating_due_on' => $end->subDays(3)->toDateString(),
        ];
        if ($cleared) {
            $updates += [
                'cleared_at' => $end->addDays(5)->setTime(11, 0),
                'cleared_by_user_id' => $coordinatorUser?->id,
                'clearance_remarks' => 'All OJT requirements are complete.',
                'certificate_number' => 'OJT-'.$end->format('Y').'-'.str_pad((string) $placement->id, 5, '0', STR_PAD_LEFT),
            ];
        }
        $placement->forceFill($updates)->save();
        $application->update(['status' => ApplicationStatus::Completed]);
    }

    // ------------------------------------------------------------------ pictures

    /** A logo for each seeded company (and one for any company without), and 3 photos of each seeded company. */
    private function companyPictures(array $companies): void
    {
        $disk = Storage::disk('public');
        $captions = ['Our office building', 'The open workspace', 'Meeting room'];
        foreach ($companies as $i => $company) {
            $employer = $company['employer'];
            $slug = Data::slug($employer->company_name);
            $logo = 'companies/logos/'.$slug.'.png';
            if (blank($employer->logo) || ! $disk->exists($employer->logo)) {
                $disk->put($logo, Images::logo($employer->company_name, $i + 2));
                $employer->forceFill(['logo' => $logo])->save();
            }
            foreach ($captions as $n => $caption) {
                $path = 'companies/photos/'.$slug.'-'.($n + 1).'.jpg';
                if (! $disk->exists($path)) {
                    $disk->put($path, Images::officePhoto($employer->company_name, $n, $i + 11));
                }
                CompanyPhoto::query()->firstOrCreate(['employer_id' => $employer->id, 'image_path' => $path], ['caption' => $caption]);
            }
        }
    }

    /**
     * A picture for everyone who has none, never replacing one they have: initials on a coloured background for
     * people, a logo mark for companies. The file name is the user's id, so running again rewrites the same file.
     */
    private function avatars(): void
    {
        $disk = Storage::disk('public');
        $made = 0;
        foreach (User::query()->with(['student', 'coordinator', 'employer'])->orderBy('id')->get() as $user) {
            $role = $user->role->value;
            $path = match ($user->role) {
                UserRole::Student => $user->student?->profile_picture,
                UserRole::Coordinator => $user->coordinator?->profile_picture,
                UserRole::Employer => $user->employer?->logo,
                default => $user->avatar,
            };
            if (filled($path) && $disk->exists($path)) {
                continue;
            }
            if (in_array($user->role, [UserRole::Student, UserRole::Coordinator], true) && ! ($user->student ?? $user->coordinator)) {
                continue; // no profile row to put the picture on
            }
            if ($user->role === UserRole::Employer && ! $user->employer) {
                continue;
            }

            $file = 'avatars/'.$role.'/'.$user->id.'.png';
            $disk->put($file, $user->role === UserRole::Employer
                ? Images::logo($user->employer->company_name, $user->id)
                : Images::avatar($user->name, $user->id));
            match ($user->role) {
                UserRole::Student => $user->student->forceFill(['profile_picture' => $file])->save(),
                UserRole::Coordinator => $user->coordinator->forceFill(['profile_picture' => $file])->save(),
                UserRole::Employer => $user->employer->forceFill(['logo' => $file])->save(),
                default => $user->forceFill(['avatar' => $file])->save(),
            };
            $made++;
        }
        $this->summary['pictures_made'] = $made;
    }

    private function storageLink(): void
    {
        $link = public_path('storage');
        // lstat also sees a link that PHP cannot follow (Windows); the app serves /storage itself in that case.
        if (is_link($link) || is_dir($link) || @lstat($link) !== false) {
            return; // already there
        }
        try {
            $this->command?->call('storage:link');
        } catch (\Throwable $exception) {
            $this->command?->warn('Could not create the storage link ('.$exception->getMessage().'). Run php artisan storage:link as an administrator; until then /storage serves the files.');
        }
    }

    // ------------------------------------------------------------------ the report

    private function report(array $coordinators, array $companies, array $students): void
    {
        $command = $this->command;
        if (! $command) {
            return;
        }

        $perRole = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $command->newLine();
        $command->info('Presentation data ready.');
        $command->table(['Role', 'Accounts in the system'], collect(UserRole::cases())->map(fn (UserRole $role) => [$role->value, $perRole[$role->value] ?? 0])->all());
        $command->table(['Item', 'Count'], [
            ['Companies (with logo and photos)', Employer::query()->count().' ('.CompanyPhoto::query()->count().' photos)'],
            ['Placements', Placement::query()->count().' ('.Placement::query()->where('status', 'active')->count().' ongoing, '.Placement::query()->where('status', 'completed')->count().' completed, '.Placement::query()->where('status', 'pending_validation')->count().' to validate)'],
            ['Certificates (competencies)', StudentCompetency::query()->count()],
            ['Competency scores', StudentCompetencyScore::query()->count().' ('.Student::query()->has('competencyScores')->count().' students)'],
            ['Time records / weekly reports', AttendanceLog::query()->count().' / '.JournalEntry::query()->count()],
            ['Monthly appraisals / final evaluations', Appraisal::query()->count().' / '.Evaluation::query()->count()],
        ]);

        $sample = [];
        foreach (['Student' => $students[1]['user'] ?? null, 'Student without an employer' => $students[10]['user'] ?? null, 'Coordinator' => ($coordinators['BSHM'][0] ?? null)?->user, 'HR / Company' => $companies[0]['hr'] ?? null, 'Supervisor' => $companies[0]['supervisor'] ?? null] as $label => $user) {
            if ($user) {
                $sample[] = [$label, $user->name, $user->email, Data::PASSWORD];
            }
        }
        $command->info('Sample logins (the existing admin and coordinator accounts are unchanged):');
        $command->table(['Role', 'Name', 'Email', 'Password'], $sample);
    }
}
