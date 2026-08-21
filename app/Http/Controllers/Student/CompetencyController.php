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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetencyController extends Controller
{
    public function __construct(private ActivityLogService $activityLog, private FileUploadService $fileUpload) {}

    public function index(Request $request)
    {
        $student = Auth::user()->student;
        $query = $student->competencies();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('proficiency_level', $request->level);
        }

        $sortBy = in_array($request->get('sort_by'), ['name', 'obtained_at', 'created_at', 'proficiency_level'], true) ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $competencies = $query->paginate(12);

        $totalCompetencies = $student->competencies()->count();
        $technicalSkills = $student->competencies()
            ->whereIn('category', ['technical', 'skill', 'workshop', 'training'])
            ->count();
        $softSkills = $student->competencies()->where('category', 'soft')->count();
        $certifications = $student->competencies()->where('category', 'certification')->count();

        return view('student.competencies.index', [
            'competencies' => $competencies,
            'categories' => CompetencyCategory::cases(),
            'levels' => ProficiencyLevel::cases(),
            'stats' => [
                'total' => $totalCompetencies,
                'technical' => $technicalSkills,
                'soft' => $softSkills,
                'certifications' => $certifications,
                'average_level' => $this->calculateAverageLevel($student),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'category' => $request->category ?? '',
                'level' => $request->level ?? '',
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function create()
    {
        return view('student.competencies.create', [
            'categories' => CompetencyCategory::cases(),
            'levels' => ProficiencyLevel::cases(),
        ]);
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
            return back()->with('error', 'You already have this competency. Please edit the existing one instead.');
        }

        $data = collect($validated)->except('evidence')->all();
        $data['evidence_path'] = $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$student->id, (string) $student->id);
        $data['evidence_name'] = $request->file('evidence')->getClientOriginalName();
        $data['verification_status'] = 'evidence_submitted';
        $competency = $student->competencies()->create($data);
        $student->calculateProfileCompletion();

        $this->activityLog->log(Auth::user(), 'competency_create', ['competency_id' => $competency->id]);

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
            return back()->with('error', 'You already have another competency with this name and category.');
        }

        $data = collect($validated)->except('evidence')->all();
        if ($request->hasFile('evidence')) {
            $this->fileUpload->delete($competency->evidence_path);
            $data['evidence_path'] = $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$competency->student_id, (string) $competency->student_id);
            $data['evidence_name'] = $request->file('evidence')->getClientOriginalName();
            $data['verification_status'] = 'evidence_submitted';
        }
        $competency->update($data);
        Auth::user()->student->calculateProfileCompletion();

        $this->activityLog->log(Auth::user(), 'competency_update', ['competency_id' => $competency->id]);

        return redirect()->route('student.competencies.index')
            ->with('success', 'Competency updated successfully!');
    }

    public function destroy(StudentCompetency $competency)
    {
        $this->authorize('delete', $competency);

        $competencyId = $competency->id;
        $this->fileUpload->delete($competency->evidence_path);
        $competency->delete();
        Auth::user()->student->calculateProfileCompletion();

        $this->activityLog->log(Auth::user(), 'competency_delete', ['competency_id' => $competencyId]);

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
