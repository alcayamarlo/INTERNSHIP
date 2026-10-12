<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicProgramRequest;
use App\Http\Requests\Admin\AcademicSettingsRequest;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\DeploymentRequirement;
use App\Models\Major;
use App\Models\Program;
use App\Services\AcademicCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicCatalogController extends Controller
{
    public function index()
    {
        $calendar = app(AcademicCalendar::class);

        return view('admin.academics.index', [
            'programs' => Program::with(['coordinator.user'])->withCount(['students', 'majors'])->orderBy('code')->get(),
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
        return $this->programForm(new Program);
    }

    public function store(AcademicProgramRequest $request)
    {
        $program = DB::transaction(function () use ($request): Program {
            $program = Program::create($this->programAttributes($request));
            $this->syncMajors($program, $request->input('majors'));

            return $program;
        });

        return redirect()->route('admin.academics.index')->with('success', 'Program created.');
    }

    public function edit(Program $program)
    {
        return $this->programForm($program);
    }

    public function update(AcademicProgramRequest $request, Program $program)
    {
        DB::transaction(function () use ($request, $program): void {
            $program->update($this->programAttributes($request));
            $this->syncMajors($program, $request->input('majors'));
            $program->students()->update(['program' => $program->display_name]);
        });

        return redirect()->route('admin.academics.index')->with('success', 'Program updated.');
    }

    public function updateSettings(AcademicSettingsRequest $request)
    {
        $settings = AcademicSetting::current();
        $settings->update($request->safe()->except(['_token', '_method']));
        $settings->summer_enabled = $request->boolean('summer_enabled');
        $settings->require_fourth_year_standing = $request->boolean('require_fourth_year_standing');
        $settings->require_major_subjects_passed = $request->boolean('require_major_subjects_passed');
        $settings->save();

        return redirect()->route('admin.academics.index')->with('success', 'Academic settings saved.');
    }

    private function programForm(Program $program)
    {
        return view('admin.academics.program-form', [
            'program' => $program->load('majors'),
            'coordinators' => Coordinator::with('user')->orderBy('id')->get(),
        ]);
    }

    private function programAttributes(AcademicProgramRequest $request): array
    {
        return [
            ...$request->safe()->only([
                'name',
                'code',
                'required_internship_hours',
                'coordinator_id',
            ]),
            'required_hours_confirmed' => $request->boolean('required_hours_confirmed'),
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
