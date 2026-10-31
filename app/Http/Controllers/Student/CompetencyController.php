<?php

namespace App\Http\Controllers\Student;

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competency\StoreCompetencyRequest;
use App\Http\Requests\Competency\UpdateCompetencyRequest;
use App\Models\StudentCompetency;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\StudentCompetencyOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompetencyController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload
    ) {}

    public function index(Request $request)
    {
        $student = Auth::user()->student;
        $overview = app(StudentCompetencyOverviewService::class)->forStudent(
            $student,
            $request->string('category')->toString() ?: null
        );

        return view('student.competencies.index', [
            'overview' => $overview,
            'competencies' => $overview['skills'],
        ]);
    }

    public function create()
    {
        // Adding a competency is a modal on the index page.
        return redirect()->route('student.competencies.index', ['add' => 1]);
    }

    public function store(StoreCompetencyRequest $request)
    {
        $student = Auth::user()->student;
        $validated = $request->validated();

        $exists = $student->competencies()
            ->where('name', $validated['name'])
            ->where('category', $validated['category'])
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You already have this competency. Please edit the existing one instead.'], 409);
            }

            return back()->with('error', 'You already have this competency. Please edit the existing one instead.');
        }

        $path = $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$student->id, (string) $student->id);

        try {
            $competency = DB::transaction(function () use ($validated, $request, $student, $path) {
                $data = collect($validated)->except('evidence')->all();
                $data['evidence_path'] = $path;
                $data['evidence_name'] = $request->file('evidence')->getClientOriginalName();
                $competency = $student->competencies()->create($data);
                $student->calculateProfileCompletion();
                $this->activityLog->log(Auth::user(), 'competency_create', ['competency_id' => $competency->id]);

                return $competency;
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Competency added successfully!']);
        }

        return redirect()->route('student.competencies.index')
            ->with('success', 'Competency added successfully!');
    }

    public function show(StudentCompetency $competency)
    {
        $this->authorize('view', $competency);

        return view('student.competencies.show', compact('competency'));
    }

    public function edit(StudentCompetency $competency)
    {
        $this->authorize('update', $competency);

        return view('student.competencies.edit', [
            'competency' => $competency,
            'categories' => CompetencyCategory::cases(),
            'levels' => ProficiencyLevel::cases(),
        ]);
    }

    public function update(UpdateCompetencyRequest $request, StudentCompetency $competency)
    {
        $this->authorize('update', $competency);

        $validated = $request->validated();

        $duplicate = Auth::user()->student->competencies()
            ->where('name', $validated['name'])
            ->where('category', $validated['category'])
            ->where('id', '!=', $competency->id)
            ->exists();

        if ($duplicate) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You already have another competency with this name and category.'], 409);
            }

            return back()->with('error', 'You already have another competency with this name and category.');
        }

        $previousPath = $competency->evidence_path;
        $newPath = null;

        if ($request->hasFile('evidence')) {
            $newPath = $this->fileUpload->upload(
                $request->file('evidence'),
                'competencies/'.$competency->student_id,
                (string) $competency->student_id
            );
        }

        try {
            DB::transaction(function () use ($validated, $request, $competency, $newPath) {
                $data = collect($validated)->except('evidence')->all();
                if ($newPath) {
                    $data['evidence_path'] = $newPath;
                    $data['evidence_name'] = $request->file('evidence')->getClientOriginalName();
                }
                $competency->update($data);
                Auth::user()->student->calculateProfileCompletion();

                $this->activityLog->log(Auth::user(), 'competency_update', ['competency_id' => $competency->id]);
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                $this->fileUpload->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $previousPath !== $newPath) {
            $this->fileUpload->delete($previousPath);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Competency updated successfully!']);
        }

        return redirect()->route('student.competencies.index')
            ->with('success', 'Competency updated successfully!');
    }

    public function destroy(StudentCompetency $competency)
    {
        $this->authorize('delete', $competency);

        $path = $competency->evidence_path;

        DB::transaction(function () use ($competency) {
            $competency->delete();
            Auth::user()->student->calculateProfileCompletion();
            $this->activityLog->log(Auth::user(), 'competency_delete', ['competency_id' => $competency->id]);
        });

        $this->fileUpload->delete($path);
        return redirect()->route('student.competencies.index')
            ->with('success', 'Competency deleted successfully!');
    }

    private function calculateAverageLevel($student): string
    {
        $competencies = $student->competencies()->get();

        if ($competencies->isEmpty()) {
            return 'N/A';
        }

        $average = $competencies->avg(fn ($comp) => $comp->proficiency_level->score());

        return match (true) {
            $average < 40 => 'Beginner',
            $average < 70 => 'Intermediate',
            $average < 85 => 'Advanced',
            default => 'Expert',
        };
    }
}
