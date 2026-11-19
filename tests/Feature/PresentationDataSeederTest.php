<?php

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\AttendanceLog;
use App\Models\CompanyPhoto;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\StudentCompetencyScore;
use App\Models\Supervisor;
use App\Models\User;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\PresentationDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** The counts that must not change when the seeder runs again. */
function pdCounts(): array
{
    return [
        'users' => User::count(), 'students' => Student::count(), 'coordinators' => Coordinator::count(), 'employers' => Employer::count(),
        'supervisors' => Supervisor::count(), 'photos' => CompanyPhoto::count(), 'scores' => StudentCompetencyScore::count(),
        'certificates' => StudentCompetency::count(), 'placements' => Placement::count(), 'logs' => AttendanceLog::count(),
        'reports' => JournalEntry::count(), 'appraisals' => Appraisal::count(), 'evaluations' => Evaluation::count(),
    ];
}

it('seeds the presentation data without touching what is already there, and again without adding anything', function () {
    Storage::fake('public');
    $this->seed(AcademicCatalogSeeder::class);
    $institution = Institution::create(['name' => "St. Cecilia's College-Cebu, Inc."]);
    $bsit = Program::where('code', 'BSIT')->firstOrFail();

    // What a real database already has: the BSIT coordinator, and a student who already has a profile picture.
    $existingCoordinator = User::factory()->create(['role' => UserRole::Coordinator, 'name' => 'John Carlo Ababan', 'email' => 'coordinator@skillbridge.test']);
    Coordinator::create(['user_id' => $existingCoordinator->id, 'institution_id' => $institution->id, 'program_id' => $bsit->id]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'System Administrator']);
    $claire = User::factory()->create(['role' => UserRole::Student, 'name' => 'Claire Bohol']);
    Storage::disk('public')->put('profiles/claire.png', 'her real picture');
    $claireStudent = Student::create(['user_id' => $claire->id, 'institution_id' => $institution->id, 'program_id' => $bsit->id, 'profile_picture' => 'profiles/claire.png']);
    // An account seeded earlier with an @example.com address: it must be renamed, not duplicated.
    $legacy = User::factory()->create(['role' => UserRole::Student, 'name' => 'Hazel Villaflor', 'email' => 'hazel.villaflor@example.com', 'password' => 'password']);

    $this->seed(PresentationDataSeeder::class);
    $first = pdCounts();

    // ---- people: 10 students (2 per department), 10 companies with an HR account and a supervisor each, 10 coordinators
    $seededStudents = Student::where('student_id_number', 'like', 'SCC-23-000240%')->orWhere('student_id_number', 'like', 'SCC-23-00002401%')->with('user')->get();
    // 70 students: 10 with an OJT at a company, and 60 with no employer at all; 14 in each department.
    expect($seededStudents)->toHaveCount(70)
        ->and($seededStudents->groupBy('program_id')->map->count()->values()->all())->toBe([14, 14, 14, 14, 14])
        ->and(Employer::count())->toBe(10)
        ->and(Supervisor::count())->toBe(10)
        ->and(Coordinator::count())->toBe(10);
    foreach (Program::all() as $program) {
        expect(Coordinator::where('program_id', $program->id)->count())->toBe(2, "department {$program->code} has 2 coordinators");
    }
    expect(Coordinator::find($existingCoordinator->coordinator->id)->program_id)->toBe($bsit->id);

    // The seeder writes personal data through the models: every value in the encrypted columns is encrypted in the table.
    expectPersonalDataStoredEncrypted();

    // Realistic gmail.com emails (none left on example.com), the system's own emails to them switched off, the password "password" (hashed), signed in without a code.
    $seededUsers = User::whereNotIn('id', [$admin->id, $claire->id, $existingCoordinator->id])->get();
    expect($seededUsers)->toHaveCount(99) // 70 students + 10 HR + 10 supervisors + 9 coordinators
        ->and($seededUsers->pluck('email')->unique())->toHaveCount(99)
        ->and($seededUsers->every(fn (User $user) => str_ends_with($user->email, '@gmail.com') && ! $user->email_notifications))->toBeTrue()
        // Flagged, so two-factor codes are never emailed to these made-up mailboxes.
        ->and($seededUsers->every(fn (User $user) => $user->demo_account))->toBeTrue()
        ->and($legacy->fresh()->email)->toBe('hazel.villaflor@gmail.com')->and(User::where('name', 'Hazel Villaflor')->count())->toBe(1)
        ->and($seededUsers->every(fn (User $user) => Hash::check('password', $user->password) && $user->email_verified_at !== null && ! $user->must_change_password))->toBeTrue()
        ->and($seededUsers->groupBy(fn (User $user) => $user->role->value)->map->count()->all())->toEqualCanonicalizing(['student' => 70, 'employer' => 10, 'coordinator' => 9, 'supervisor' => 10]);

    // ---- companies: logo, 3 photos that exist, supervisor of the same company
    foreach (Employer::with('photos')->get() as $employer) {
        expect(Storage::disk('public')->exists($employer->logo))->toBeTrue()
            ->and($employer->photos)->toHaveCount(3)
            ->and($employer->photos->every(fn (CompanyPhoto $photo) => Storage::disk('public')->exists($photo->image_path)))->toBeTrue()
            ->and($employer->verified_at)->not->toBeNull();
    }

    // ---- competency scores: 10 per student, in range, not the same for everyone
    $ranges = StudentCompetencyScore::SKILLS;
    foreach ($seededStudents as $student) {
        $scores = $student->competencyScores->pluck('score', 'skill_name');
        expect($scores)->toHaveCount(10);
        foreach ($ranges as $skill => [$min, $max]) {
            expect($scores[$skill])->toBeBetween($min, $max);
        }
    }
    expect($seededStudents->map(fn (Student $student) => $student->competencyScores->sortBy('skill_name')->pluck('score')->implode(','))->unique())->toHaveCount(70);

    // ---- certificates: 5 per student, verified, drawn, within the last 2 years
    foreach ($seededStudents as $student) {
        $certificates = StudentCompetency::where('student_id', $student->id)->get();
        expect($certificates)->toHaveCount(5)
            ->and($certificates->every(fn ($c) => Storage::disk('public')->exists($c->evidence_path) && $c->verification_status === 'verified'
                && $c->obtained_at->gte(now()->subYears(2)) && filled($c->issuing_organization)))->toBeTrue();
    }

    // ---- the 10 extra students: no employer (no application, placement or invitation), and nothing they have is the same as another student's
    $free = $seededStudents->filter(fn (Student $student) => (int) substr($student->student_id_number, -8) >= 24011)->values();
    $based = $seededStudents->diff($free);
    expect($free)->toHaveCount(60)
        ->and(\App\Models\InternshipApplication::withTrashed()->whereIn('student_id', $free->pluck('id'))->count())->toBe(0)
        ->and(Placement::whereIn('student_id', $free->pluck('id'))->count())->toBe(0)
        ->and(\App\Models\InternshipInvitation::whereIn('student_id', $free->pluck('id'))->count())->toBe(0);
    $titles = fn ($students) => StudentCompetency::whereIn('student_id', $students->pluck('id'))->pluck('name');
    $skills = fn ($students) => \App\Models\StudentSkill::whereIn('student_id', $students->pluck('id'))->with('skill')->get()->map(fn ($s) => $s->skillTag?->name ?? $s->skill?->name ?? $s->skill_tag_id);
    $firstTen = $free->filter(fn (Student $student) => (int) substr($student->student_id_number, -8) <= 24020);
    $bulk = $free->diff($firstTen);
    expect($firstTen)->toHaveCount(10)->and($bulk)->toHaveCount(50)
        ->and($titles($firstTen))->toHaveCount(50)->and($titles($firstTen)->unique())->toHaveCount(50)
        ->and($titles($firstTen)->intersect($titles($based)))->toBeEmpty()
        ->and(\App\Models\StudentSkill::whereIn('student_id', $firstTen->pluck('id'))->pluck('skill_tag_id')->unique())->toHaveCount(60)
        ->and(\App\Models\StudentSkill::whereIn('student_id', $firstTen->pluck('id'))->pluck('skill_tag_id')->intersect(\App\Models\StudentSkill::whereIn('student_id', $based->pluck('id'))->pluck('skill_tag_id')))->toBeEmpty();
    // The 50 others: 5 different certificates and 6 skills each, and a different combination for every student of a department.
    foreach ($bulk->groupBy('program_id') as $department) {
        $certificateSets = $department->map(fn (Student $student) => StudentCompetency::where('student_id', $student->id)->orderBy('name')->pluck('name')->implode('|'));
        $skillSets = $department->map(fn (Student $student) => \App\Models\StudentSkill::where('student_id', $student->id)->orderBy('skill_tag_id')->pluck('skill_tag_id')->implode(','));
        expect($department)->toHaveCount(10)->and($certificateSets->unique())->toHaveCount(10)->and($skillSets->unique())->toHaveCount(10);
        foreach ($department as $student) {
            expect(StudentCompetency::where('student_id', $student->id)->pluck('name')->unique())->toHaveCount(5)
                ->and(\App\Models\StudentSkill::where('student_id', $student->id)->count())->toBe(6);
        }
    }
    // Every student has an illustrated profile picture on the public disk.
    foreach ($seededStudents as $student) {
        expect($student->fresh()->profile_picture)->toStartWith('avatars/student/')->and(Storage::disk('public')->exists($student->fresh()->profile_picture))->toBeTrue();
    }

    // ---- the OJT: every stage, each student's company and supervisor consistent, coordinator of the same department
    $placements = Placement::whereIn('student_id', $seededStudents->pluck('id'))->with(['student', 'employer', 'supervisorUser.supervisor'])->get();
    expect($placements)->toHaveCount(10)
        ->and($placements->where('status', Placement::STATUS_ACTIVE))->toHaveCount(5)
        ->and($placements->where('status', Placement::STATUS_COMPLETED))->toHaveCount(3)
        ->and($placements->whereNotNull('cleared_at'))->toHaveCount(2)
        ->and($placements->where('status', Placement::STATUS_PENDING_VALIDATION))->toHaveCount(2)
        ->and($placements->every(fn (Placement $p) => $p->supervisorUser?->supervisor?->employer_id === $p->employer_id))->toBeTrue()
        ->and($placements->pluck('employer_id')->unique())->toHaveCount(10);
    foreach ($placements->where('status', Placement::STATUS_COMPLETED) as $placement) {
        expect($placement->approvedHours())->toBeGreaterThanOrEqual($placement->required_hours)
            ->and($placement->evaluation)->not->toBeNull()
            ->and((float) $placement->evaluation->average_score)->toBeBetween(3.5, 5.0);
    }
    foreach ($placements->where('status', '!=', Placement::STATUS_PENDING_VALIDATION) as $placement) {
        $coordinator = Coordinator::find(Coordinator::whereHas('user', fn ($q) => $q->whereKey($placement->validated_by_user_id))->value('id'));
        expect($coordinator)->not->toBeNull()->and($coordinator->canAccessStudent($placement->student))->toBeTrue();
    }
    expect(Appraisal::whereNotNull('adviser_submitted_at')->count())->toBeGreaterThan(10)
        ->and(JournalEntry::where('status', 'acknowledged')->count())->toBeGreaterThan(30)
        ->and(AttendanceLog::where('status', AttendanceLog::STATUS_PENDING)->count())->toBeGreaterThan(0);

    // ---- pictures: everyone has one, and an existing picture is never replaced
    expect($claireStudent->fresh()->profile_picture)->toBe('profiles/claire.png')
        ->and(Storage::disk('public')->get('profiles/claire.png'))->toBe('her real picture')
        ->and($admin->fresh()->avatar)->not->toBeNull()
        ->and($existingCoordinator->coordinator->fresh()->profile_picture)->not->toBeNull();
    foreach (User::with(['student', 'coordinator', 'employer'])->get() as $user) {
        $picture = match ($user->role) {
            UserRole::Student => $user->student?->profile_picture,
            UserRole::Coordinator => $user->coordinator?->profile_picture,
            UserRole::Employer => $user->employer?->logo,
            default => $user->avatar,
        };
        expect($picture)->not->toBeNull("{$user->email} has a picture")->and(Storage::disk('public')->exists($picture))->toBeTrue();
    }

    // ---- again: nothing is added, changed or duplicated
    $this->seed(PresentationDataSeeder::class);
    expect(pdCounts())->toBe($first);
});
