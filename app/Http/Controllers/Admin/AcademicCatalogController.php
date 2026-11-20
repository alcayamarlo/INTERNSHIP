<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicProgramRequest;
use App\Http\Requests\Admin\AcademicSettingsRequest;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\User;
use App\Models\DeploymentRequirement;
use App\Models\Major;
use App\Models\Program;
use App\Services\AcademicCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicCatalogController extends Controller
{
    public function index()
    {
        $calendar = app(AcademicCalendar::class);

        return view('admin.academics.index', [
            'academicsRoute' => $this->routePrefix(),
            'programs' => Program::with(['coordinators.user', 'majors'])->withCount(['students', 'majors'])
                // A coordinator sees only their own department.
                ->when($this->isCoordinator(), fn ($query) => $query->whereKey(Auth::user()->coordinator?->program_id ?? 0))
                ->orderBy('code')->get(),
            'canManagePrograms' => ! $this->isCoordinator(),
            'coordinatorChoices' => $this->isCoordinator() ? null : User::query()
                ->where('role', UserRole::Coordinator->value)->where('is_active', true)->whereNull('archived_at')->whereNull('anonymised_at')
                ->whereHas('coordinator')->with('coordinator.program')->orderBy('name')->get(),
            'deploymentRequirements' => DeploymentRequirement::query()->orderBy('sort_order')->orderBy('id')->get(),
            'settings' => AcademicSetting::current(),
            'monthOptions' => collect(range(1, 12))
                ->mapWithKeys(fn (int $month) => [$month => CarbonImmutable::create(2024, $month, 1)->format('F')]),
            'termOptions' => $calendar->termOptions(AcademicSetting::current()),
            'termLabels' => $calendar->termLabels(),
        ]);
    }

    public function create()
    {
        abort_if($this->isCoordinator(), 403);

        return $this->programForm(new Program);
    }

    public function store(AcademicProgramRequest $request)
    {
        abort_if($this->isCoordinator(), 403);

        $program = DB::transaction(function () use ($request): Program {
            $program = Program::create($this->programAttributes($request));
            $this->syncMajors($program, $request->input('majors'));
            $this->syncCoordinators($program, $request);

            return $program;
        });

        return redirect()->route($this->routePrefix().'index')->with('success', 'Program created.');
    }

    public function edit(Program $program)
    {
        $this->authorizeProgram($program);

        return $this->programForm($program);
    }

    public function update(AcademicProgramRequest $request, Program $program)
    {
        $this->authorizeProgram($program);

        DB::transaction(function () use ($request, $program): void {
            $program->update($this->programAttributes($request));
            $this->syncMajors($program, $request->input('majors'));
            $this->syncCoordinators($program, $request);
            $program->students()->update(['program' => $program->display_name]);
        });

        return redirect()->route($this->routePrefix().'index')->with('success', 'Program updated.');
    }

    public function assignCoordinator(\Illuminate\Http\Request $request, Program $program)
    {
        abort_if($this->isCoordinator(), 403);

        $data = $request->validate([
            'coordinators' => ['nullable', 'array', 'max:'.Coordinator::MAX_PER_DEPARTMENT],
            'coordinators.*' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('users', 'id')->where('role', UserRole::Coordinator->value)->where('is_active', true)->whereNull('archived_at')->whereNull('anonymised_at')],
        ]);
        $userIds = array_values(array_filter(array_map('intval', $data['coordinators'] ?? [])));

        DB::transaction(function () use ($program, $userIds): void {
            Program::query()->lockForUpdate()->find($program->id);
            if (Coordinator::query()->whereIn('user_id', $userIds)->count() !== count($userIds)) {
                throw ValidationException::withMessages(['coordinators' => 'Choose a user who has a coordinator profile.']);
            }
            Coordinator::query()->where('program_id', $program->id)->whereNotIn('user_id', $userIds)->update(['program_id' => null]);
            Coordinator::query()->whereIn('user_id', $userIds)->update(['program_id' => $program->id]);
        });

        return redirect()->route($this->routePrefix().'index')->with('success', 'Coordinator updated for '.$program->name.'.');
    }

    public function destroy(Program $program)
    {
        abort_if($this->isCoordinator(), 403);

        if ($program->students()->exists()) {
            return redirect()->route($this->routePrefix().'index')->with('error', 'Cannot delete '.$program->name.': students are enrolled in it.');
        }

        DB::transaction(function () use ($program): void {
            Coordinator::query()->where('program_id', $program->id)->update(['program_id' => null]);
            $program->majors()->delete();
            $program->delete();
        });

        return redirect()->route($this->routePrefix().'index')->with('success', 'Program deleted.');
    }

    public function updateSettings(AcademicSettingsRequest $request)
    {
        $settings = AcademicSetting::current();
        $settings->update($request->safe()->except(['_token', '_method']));
        $settings->require_fourth_year_standing = $request->boolean('require_fourth_year_standing');
        $settings->require_major_subjects_passed = $request->boolean('require_major_subjects_passed');
        $settings->save();

        return redirect()->route($this->routePrefix().'index')->with('success', 'Academic settings saved.');
    }

    private function isCoordinator(): bool
    {
        return request()->routeIs('coordinator.*');
    }

    /** Admins edit any department; a coordinator only their own. Only admins add departments. */
    private function authorizeProgram(Program $program): void
    {
        abort_if($this->isCoordinator() && (int) $program->id !== (int) Auth::user()->coordinator?->program_id, 403);
    }

    /** Admins use admin.academics.*, coordinators coordinator.academics.* (the same pages). */
    private function routePrefix(): string
    {
        return request()->routeIs('coordinator.*') ? 'coordinator.academics.' : 'admin.academics.';
    }

    private function programForm(Program $program)
    {
        return view('admin.academics.program-form', [
            'academicsRoute' => $this->routePrefix(),
            'program' => $program->load(['majors', 'coordinators.user']),
            // Admin only: every active user with the coordinator role (and a coordinator profile), to assign one.
            'coordinatorChoices' => $this->isCoordinator() ? null : User::query()
                ->where('role', UserRole::Coordinator->value)->where('is_active', true)->whereNull('archived_at')->whereNull('anonymised_at')
                ->whereHas('coordinator')->with('coordinator.program')->orderBy('name')->get(),
        ]);
    }

    /**
     * Admin only. Makes the chosen coordinators (at most 2) the department's coordinators: a coordinator who was in another
     * department moves here, and one who was here and is not chosen is left without a department. The program row is locked
     * while this runs, so two admins cannot end up with 3 coordinators in it.
     */
    private function syncCoordinators(Program $program, AcademicProgramRequest $request): void
    {
        if ($this->isCoordinator() || ! $request->has('coordinators_submitted')) {
            return;
        }
        $userIds = array_map('intval', (array) $request->input('coordinators', []));
        Program::query()->lockForUpdate()->find($program->id);

        $profiles = Coordinator::query()->whereIn('user_id', $userIds)->pluck('user_id');
        if ($profiles->count() !== count($userIds) || count($userIds) > Coordinator::MAX_PER_DEPARTMENT) {
            throw ValidationException::withMessages(['coordinators' => 'Choose one user who has the coordinator role. A program has one coordinator.']);
        }

        Coordinator::query()->where('program_id', $program->id)->whereNotIn('user_id', $userIds)->update(['program_id' => null]);
        Coordinator::query()->whereIn('user_id', $userIds)->update(['program_id' => $program->id]);
    }

    private function programAttributes(AcademicProgramRequest $request): array
    {
        return [
            ...$request->safe()->only([
                'name',
                'code',
                'required_internship_hours',
            ]),
            'required_hours_confirmed' => true, // hours entered by an admin are the school's confirmed hours
        ];
    }

    private function syncMajors(Program $program, ?string $input): void
    {
        $names = collect(preg_split('/\r\n|\r|\n/', (string) $input))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values();
        $existing = $program->majors()->get()->keyBy(fn (Major $major) => mb_strtolower($major->name));
        $keepIds = [];

        foreach ($names as $name) {
            $major = $existing->get(mb_strtolower($name));

            if (! $major) {
                $major = $program->majors()->create(['name' => $name]);
            }

            $keepIds[] = $major->id;
        }

        $removed = $program->majors()->whereNotIn('id', $keepIds)->get();

        if ($removed->contains(fn (Major $major) => $major->students()->exists())) {
            throw ValidationException::withMessages([
                'majors' => 'A major assigned to a student cannot be removed. Reassign those students first.',
            ]);
        }

        $program->majors()->whereNotIn('id', $keepIds)->delete();
    }
}
