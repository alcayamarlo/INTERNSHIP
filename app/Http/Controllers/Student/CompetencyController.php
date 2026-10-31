<?php

namespace App\Http\Controllers\Student;

use App\Enums\CertificateType;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competency\CompleteLegacyCertificateRequest;
use App\Http\Requests\Competency\StoreCompetencyRequest;
use App\Http\Requests\Competency\UpdateCompetencyRequest;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\StudentCompetencySkill;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\StudentCompetencyOverviewService;
use App\Support\CatalogName;
use App\Support\UploadDisks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * "My Certificates" (stored as student_competencies). Only the owning student can write;
 * coordinators and employers only ever read these records elsewhere.
 */
class CompetencyController extends Controller
{
    public const SUCCESS_MESSAGE = 'Certificate added. It now shows on your profile and resume.';

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
            'legacyCertificates' => $this->legacyCertificates($student),
            'certificateTypes' => CertificateType::cases(),
            'skillTypes' => SkillType::cases(),
            'levels' => [ProficiencyLevel::Beginner, ProficiencyLevel::Intermediate, ProficiencyLevel::Advanced],
            'maxSkills' => StudentCompetencySkill::MAX_PER_CERTIFICATE,
            'editId' => $request->integer('edit') ?: null,
        ]);
    }

    public function create()
    {
        return redirect()->route('student.competencies.index', ['add' => 1]);
    }

    /** Autocomplete for "Skills you learned": the skill catalog plus skills used on certificates. */
    public function suggestSkills(Request $request): JsonResponse
    {
        $term = str_replace(['%', '_'], '', CatalogName::normalized((string) $request->query('q', '')));
        if ($term === '') {
            return response()->json(['suggestions' => []]);
        }

        $names = collect();
        if (Schema::hasTable('skill_tags')) {
            $names = SkillTag::query()->where('name_normalized', 'like', $term.'%')->orderBy('name')->limit(8)->pluck('name');
        }
        $names = $names->concat(
            StudentCompetencySkill::query()->where('name_normalized', 'like', $term.'%')->distinct()->orderBy('name')->limit(8)->pluck('name')
        );

        return response()->json([
            'suggestions' => $names->unique(fn (string $name) => CatalogName::normalized($name))->take(8)->values(),
        ]);
    }

    public function store(StoreCompetencyRequest $request)
    {
        $student = Auth::user()->student;
        $validated = $request->validated();
        $this->ensureNotDuplicate($student, $validated);

        $path = $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$student->id, (string) $student->id);

        try {
            $competency = DB::transaction(function () use ($validated, $request, $student, $path) {
                $competency = $student->competencies()->create([
                    ...$this->attributes($validated),
                    'evidence_path' => $path,
                    'evidence_name' => $request->file('evidence')->getClientOriginalName(),
                ]);
                $this->syncSkills($competency, $validated['skills']);
                $student->calculateProfileCompletion();
                $this->activityLog->log(Auth::user(), 'competency_create', ['competency_id' => $competency->id]);

                return $competency;
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        return $this->respond($request, $student, self::SUCCESS_MESSAGE, $competency, 201);
    }

    /** Completes an old Documents-tab certificate; the original row is kept and linked. */
    public function completeLegacy(CompleteLegacyCertificateRequest $request, Certificate $certificate)
    {
        abort_if($certificate->student_competency_id !== null, 409, 'This certificate was already completed.');
        $student = Auth::user()->student;
        $validated = $request->validated();
        $this->ensureNotDuplicate($student, $validated);
        $newPath = $request->hasFile('evidence')
            ? $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$student->id, (string) $student->id)
            : null;

        try {
            $competency = DB::transaction(function () use ($validated, $request, $student, $certificate, $newPath) {
                $competency = $student->competencies()->create([
                    ...$this->attributes($validated),
                    'evidence_path' => $newPath ?? $certificate->file_path,
                    'evidence_name' => $newPath ? $request->file('evidence')->getClientOriginalName() : basename((string) $certificate->file_path),
                ]);
                $this->syncSkills($competency, $validated['skills']);
                $certificate->update(['student_competency_id' => $competency->id]);
                $student->calculateProfileCompletion();
                $this->activityLog->log(Auth::user(), 'certificate_completed', ['competency_id' => $competency->id, 'certificate_id' => $certificate->id]);

                return $competency;
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                $this->fileUpload->delete($newPath);
            }
            throw $exception;
        }

        return $this->respond($request, $student, self::SUCCESS_MESSAGE, $competency, 201, ['completed_legacy_id' => $certificate->id]);
    }

    public function show(StudentCompetency $competency)
    {
        $this->authorize('view', $competency);
        $competency->load('learnedSkills');

        return view('student.competencies.show', compact('competency'));
    }

    public function file(StudentCompetency $competency)
    {
        $this->authorize('view', $competency);
        abort_unless($competency->evidence_path, 404);
        $disk = collect(UploadDisks::search())->first(fn (string $disk) => Storage::disk($disk)->exists($competency->evidence_path));
        abort_unless($disk, 404, 'File not found.');
        $extension = strtolower(pathinfo($competency->evidence_path, PATHINFO_EXTENSION));
        $mimeTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

        return Storage::disk($disk)->response($competency->evidence_path, $competency->evidence_name ?: basename($competency->evidence_path), [
            'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
            'Content-Disposition' => (isset($mimeTypes[$extension]) ? 'inline' : 'attachment').'; filename="'.basename($competency->evidence_path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function edit(StudentCompetency $competency)
    {
        $this->authorize('update', $competency);

        return redirect()->route('student.competencies.index', ['edit' => $competency->id]);
    }

    public function update(UpdateCompetencyRequest $request, StudentCompetency $competency)
    {
        $this->authorize('update', $competency);
        $student = Auth::user()->student;
        $validated = $request->validated();
        $this->ensureNotDuplicate($student, $validated, $competency->id);

        $previousPath = $competency->evidence_path;
        $newPath = $request->hasFile('evidence')
            ? $this->fileUpload->upload($request->file('evidence'), 'competencies/'.$competency->student_id, (string) $competency->student_id)
            : null;

        try {
            DB::transaction(function () use ($validated, $request, $competency, $newPath, $student) {
                $competency->update([
                    ...$this->attributes($validated),
                    ...($newPath ? [
                        'evidence_path' => $newPath,
                        'evidence_name' => $request->file('evidence')->getClientOriginalName(),
                    ] : []),
                ]);
                $this->syncSkills($competency, $validated['skills']);
                $student->calculateProfileCompletion();
                $this->activityLog->log(Auth::user(), 'competency_update', ['competency_id' => $competency->id]);
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                $this->fileUpload->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath && $previousPath !== $newPath) {
            $this->fileUpload->deleteIfUnreferenced($previousPath);
        }

        return $this->respond($request, $student, 'Certificate updated.', $competency->fresh());
    }

    public function destroy(Request $request, StudentCompetency $competency)
    {
        $this->authorize('delete', $competency);
        $student = Auth::user()->student;
        $path = $competency->evidence_path;

        DB::transaction(function () use ($competency, $student) {
            // A certificate completed from an old Documents-tab entry is one item to the student.
            $certificate = $competency->sourceCertificate;
            if ($certificate) {
                Portfolio::query()
                    ->where('student_id', $certificate->student_id)
                    ->where('category', 'certificate')
                    ->where('title', $certificate->title)
                    ->where('file_path', $certificate->file_path)
                    ->delete();
                $certificate->delete();
            }
            $competency->delete();
            $student->calculateProfileCompletion();
            $this->activityLog->log(Auth::user(), 'competency_delete', ['competency_id' => $competency->id]);
        });

        $this->fileUpload->deleteIfUnreferenced($path);

        return $this->respond($request, $student, 'Certificate deleted.');
    }

    private function attributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'certificate_type' => $validated['certificate_type'],
            'skill_type' => $validated['skill_type'],
            'proficiency_level' => $validated['proficiency_level'],
            'issuing_organization' => $validated['issuing_organization'] ?? null,
            'obtained_at' => $validated['obtained_at'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
    }

    private function syncSkills(StudentCompetency $competency, array $skills): void
    {
        $competency->learnedSkills()->delete();
        foreach ($skills as $name) {
            $competency->learnedSkills()->create(['name' => $name]);
        }
    }

    private function ensureNotDuplicate(Student $student, array $validated, ?int $ignoreId = null): void
    {
        $duplicate = $student->competencies()
            ->whereRaw('LOWER(name) = ?', [CatalogName::normalized($validated['name'])])
            ->where('certificate_type', $validated['certificate_type'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'You already added a certificate with this title and type.',
            ]);
        }
    }

    /** Old Documents-tab certificates that still need a type, skill type, level, and skills. */
    private function legacyCertificates(Student $student)
    {
        return $student->certificates()
            ->whereNull('student_competency_id')
            ->latest()
            ->get()
            ->map(fn (Certificate $certificate) => [
                'id' => $certificate->id,
                'title' => $certificate->title,
                'issuer' => $certificate->issuer,
                'obtained_at' => $certificate->issue_date?->toDateString(),
                'has_file' => filled($certificate->file_path),
                'evidence_name' => $certificate->file_path ? basename($certificate->file_path) : null,
                'file_url' => filled($certificate->file_path) ? route('student.certificates.preview', $certificate, false) : null,
                'complete_url' => route('student.competencies.complete-legacy', $certificate, false),
                'delete_url' => route('student.certificates.destroy', $certificate, false),
            ]);
    }

    private function respond(Request $request, Student $student, string $message, ?StudentCompetency $competency = null, int $status = 200, array $extra = []): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->route('student.competencies.index')->with('success', $message);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'id' => $competency?->id,
            'overview' => app(StudentCompetencyOverviewService::class)->forStudent($student->fresh()),
            ...$extra,
        ], $status);
    }
}
