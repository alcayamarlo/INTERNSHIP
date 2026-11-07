<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DeploymentRequirement;
use App\Models\EligibilityCriterion;
use App\Models\Program;
use App\Services\AcademicCalendar;
use App\Services\ActivityLogService;
use App\Services\EligibilityCriteria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Internship criteria per program and term. Coordinators manage the programs assigned to them;
 * admins manage all programs. Every save is a new version.
 */
class EligibilityCriteriaController extends Controller
{
    public function __construct(
        private EligibilityCriteria $criteria,
        private AcademicCalendar $calendar,
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request)
    {
        [$schoolYear, $term] = $this->selectedTerm($request);

        $programs = $this->managedPrograms()->map(fn (Program $program) => [
            'program' => $program,
            'current' => $this->criteria->latestFor($program->id, $schoolYear, $term),
        ]);

        return view('coordinator.criteria.index', [
            'programs' => $programs,
            'schoolYear' => $schoolYear,
            'term' => $term,
            ...$this->termChoices(),
            'routePrefix' => $this->routePrefix(),
            'globalRules' => $this->criteria->globalRules(),
        ]);
    }

    public function show(Request $request, Program $program)
    {
        $this->authorizeProgram($program);
        [$schoolYear, $term] = $this->selectedTerm($request);
        $versions = EligibilityCriterion::query()
            ->forTerm($program->id, $schoolYear, $term)
            ->with('createdBy:id,name')
            ->orderByDesc('version')
            ->get();

        return view('coordinator.criteria.show', [
            'program' => $program,
            'schoolYear' => $schoolYear,
            'term' => $term,
            'current' => $versions->first(),
            'versions' => $versions,
            'documents' => $this->documentChoices(),
            ...$this->termChoices(),
            'routePrefix' => $this->routePrefix(),
            'globalRules' => $this->criteria->globalRules(),
        ]);
    }

    public function store(Request $request, Program $program)
    {
        $this->authorizeProgram($program);
        $terms = $this->termChoices();
        $validated = $request->validate([
            'school_year' => ['required', Rule::in($terms['schoolYears'])],
            'term' => ['required', Rule::in(array_keys($terms['terms']))],
            'require_fourth_year' => ['nullable', 'boolean'],
            'require_major_subjects_passed' => ['nullable', 'boolean'],
            'required_hours' => ['nullable', 'integer', 'between:1,2000'],
            'required_document_ids' => ['nullable', 'array'],
            'required_document_ids.*' => ['integer', Rule::in($this->documentChoices()->modelKeys())],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'required_hours.between' => 'Enter required hours between 1 and 2,000.',
        ]);

        $criterion = $this->criteria->saveVersion($program, $validated['school_year'], $validated['term'], [
            'require_fourth_year' => $request->boolean('require_fourth_year'),
            'require_major_subjects_passed' => $request->boolean('require_major_subjects_passed'),
            'required_hours' => $validated['required_hours'] ?? null,
            'required_document_ids' => array_values(array_map('intval', $validated['required_document_ids'] ?? [])),
            'notes' => $validated['notes'] ?? null,
        ], Auth::user());

        $this->activityLog->log(Auth::user(), 'eligibility_criteria_saved', [
            'program_id' => $program->id,
            'school_year' => $criterion->school_year,
            'term' => $criterion->term,
            'version' => $criterion->version,
        ]);

        return redirect()
            ->route($this->routePrefix().'.criteria.show', [$program, 'school_year' => $criterion->school_year, 'term' => $criterion->term])
            ->with('success', "Criteria saved as version {$criterion->version}.");
    }

    /** @return Collection<int, Program> */
    private function managedPrograms(): Collection
    {
        $user = Auth::user();

        return Program::query()
            ->when(! $user->isRole(UserRole::Admin), fn ($query) => $query->where('coordinator_id', $user->coordinator?->id ?? 0))
            ->orderBy('code')
            ->get();
    }

    private function authorizeProgram(Program $program): void
    {
        $user = Auth::user();
        abort_unless(
            $user->isRole(UserRole::Admin)
                || ($user->coordinator && (int) $program->coordinator_id === (int) $user->coordinator->id),
            403,
            'You can only manage criteria for programs assigned to you.'
        );
    }

    /** @return array{0: string, 1: string} */
    private function selectedTerm(Request $request): array
    {
        $current = $this->criteria->currentTerm();
        $terms = $this->termChoices();
        $schoolYear = in_array($request->query('school_year'), $terms['schoolYears'], true) ? $request->query('school_year') : $current['school_year'];
        $term = array_key_exists((string) $request->query('term'), $terms['terms']) ? $request->query('term') : $current['term'];

        return [$schoolYear, $term];
    }

    private function termChoices(): array
    {
        return ['schoolYears' => $this->calendar->schoolYears(), 'terms' => $this->calendar->termOptions(), 'currentTerm' => $this->criteria->currentTerm()];
    }

    /** Documents students upload themselves (not on-request IDs). */
    private function documentChoices(): Collection
    {
        return DeploymentRequirement::query()
            ->where('is_active', true)
            ->where('on_request', false)
            ->where('upload_source', DeploymentRequirement::SOURCE_STUDENT)
            ->orderBy('sort_order')
            ->get();
    }

    private function routePrefix(): string
    {
        return request()->routeIs('admin.*') ? 'admin' : 'coordinator';
    }
}
