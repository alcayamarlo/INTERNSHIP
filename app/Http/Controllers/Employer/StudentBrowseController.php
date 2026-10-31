<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipInvitation;
use App\Models\Program;
use App\Models\Student;
use App\Services\CompanyVisibleProfile;
use App\Services\InternshipEligibility;
use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Browse Students. Filters run on the server; everything shown comes from CompanyVisibleProfile.
 */
class StudentBrowseController extends Controller
{
    public const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    public function __construct(
        private CompanyVisibleProfile $visibleProfile,
        private InternshipEligibility $eligibility,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'program_id' => ['nullable', 'integer'],
            'year_level' => ['nullable', 'string', 'in:'.implode(',', self::YEAR_LEVELS)],
            'skill' => ['nullable', 'string', 'max:60'],
            'certificate' => ['nullable', 'string', 'max:100'],
        ]);
        $programs = Program::orderBy('code')->get();

        $students = $this->browsable()
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->whereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$term.'%')))
            ->when($filters['program_id'] ?? null, function (Builder $query, int $programId) use ($programs) {
                $program = $programs->firstWhere('id', $programId);
                $query->where(fn (Builder $match) => $match->where('program_id', $programId)
                    ->when($program, fn (Builder $legacy) => $legacy->orWhereIn('program', [$program->name, $program->code, $program->display_name])));
            })
            ->when($filters['year_level'] ?? null, fn (Builder $query, string $year) => $query->where('year_level', $year))
            ->when($filters['skill'] ?? null, fn (Builder $query, string $skill) => $query->whereHas('studentSkills.skill',
                fn (Builder $tag) => $tag->where('name_normalized', 'like', '%'.CatalogName::normalized($skill).'%')))
            ->when($filters['certificate'] ?? null, fn (Builder $query, string $certificate) => $query->whereHas('competencies',
                fn (Builder $competency) => $competency->where(fn (Builder $match) => $match->where('name', 'like', '%'.$certificate.'%')
                    ->orWhereHas('learnedSkills', fn (Builder $learned) => $learned->where('name', 'like', '%'.$certificate.'%')))))
            ->with(['user', 'studentSkills.skill', 'competencies.learnedSkills'])
            ->orderByDesc('profile_completion')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $students->setCollection($students->getCollection()->map(fn (Student $student) => $this->visibleProfile->browseCard($student)));

        return view('employer.students.index', [
            'students' => $students,
            'filters' => $filters,
            'programs' => $programs,
            'yearLevels' => self::YEAR_LEVELS,
            ...$this->inviteContext($students->getCollection()->pluck('student_id')->all()),
        ]);
    }

    public function show(Student $student)
    {
        abort_unless($this->browsable()->whereKey($student->id)->exists(), 404);

        return view('employer.students.show', [
            'profile' => $this->visibleProfile->browseProfile($student),
            ...$this->inviteContext([$student->id]),
        ]);
    }

    /** Students who agreed to the privacy consent, allow companies to find them, and may apply. */
    private function browsable(): Builder
    {
        return $this->eligibility->constrain(Student::query()->discoverable());
    }

    /**
     * The employer's open internships for the Invite modal, and which listed students already
     * have a pending invitation from this employer.
     */
    private function inviteContext(array $studentIds): array
    {
        $employer = Auth::user()->employer;

        return [
            'openInternships' => Internship::query()->where('employer_id', $employer->id)->open()->orderBy('title')->get(['id', 'title', 'expires_at']),
            'pendingInvites' => InternshipInvitation::pending()
                ->where('employer_id', $employer->id)
                ->whereIn('student_id', $studentIds)
                ->with('internship:id,title')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($invitations) => $invitations->pluck('internship.title')->all()),
        ];
    }
}
