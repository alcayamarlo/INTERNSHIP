<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Enums\PortfolioCategory;
use App\Models\Certificate;
use App\Models\DeploymentRequirement;
use App\Models\Coordinator;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\VerificationActivity;
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload
    ) {}

    public function uploadDeploymentDocument(Request $request, Student $student): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeStudent($student);
        $validated = $request->validate([
            'deployment_requirement_id' => ['required', 'integer'],
            'file' => \App\Services\FileUploadService::documentRules(),
        ]);
        $requirement = DeploymentRequirement::active()
            ->whereKey($validated['deployment_requirement_id'])
            ->where('upload_source', DeploymentRequirement::SOURCE_COORDINATOR)
            ->first();
        if (! $requirement) {
            throw ValidationException::withMessages([
                'deployment_requirement_id' => 'Select an active coordinator-uploaded requirement.',
            ]);
        }
        $existing = $student->deploymentDocuments()
            ->where('deployment_requirement_id', $requirement->id)
            ->latest('updated_at')
            ->first();
        $oldPath = $existing?->file_path;
        $path = $this->fileUpload->upload(
            $request->file('file'),
            'portfolios/'.$student->id.'/deployment',
            'coordinator_'.$student->id
        );

        try {
            $file = DB::transaction(function () use ($student, $path, $requirement, $existing): Portfolio {
                $coordinator = $this->coordinator();
                $portfolio = $existing ?? new Portfolio([
                    'student_id' => $student->id,
                    'type' => 'transcript',
                    'category' => PortfolioCategory::DeploymentDocument,
                    'deployment_requirement_id' => $requirement->id,
                ]);
                $portfolio->fill([
                    'title' => $requirement->name,
                    'description' => 'Uploaded by the coordinator.',
                    'file_path' => $path,
                    'verification_status' => 'approved',
                    'review_notes' => null,
                    'uploaded_by_user_id' => $coordinator->user_id,
                    'reviewed_by_user_id' => $coordinator->user_id,
                    'reviewed_at' => now(),
                ])->save();
                $this->activityLog->log(Auth::user(), 'deployment_document_upload', [
                    'student_id' => $student->id,
                    'portfolio_id' => $portfolio->id,
                    'category' => PortfolioCategory::DeploymentDocument->value,
                ]);

                return $portfolio;
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        $this->fileUpload->deleteIfUnreferenced($oldPath);
        $this->notificationService->send(
            $student->user,
            'verification',
            'Document Approved',
            'The coordinator uploaded "'.$file->title.'" for you.',
            ['portfolio_id' => $file->id, 'status' => 'approved']
        );
        $student->calculateProfileCompletion();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Deployment document uploaded.',
            ]);
        }

        return back()->with('success', 'Deployment document uploaded.');
    }

    public function index(Request $request)
    {
        $coordinator = $this->coordinator();
        $students = $coordinator->scopedStudentsQuery()
            ->with([
                'user:id,name,email',
                'portfolios.reviewerUser:id,name',
                'resumes.reviewerUser:id,name',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Student $student) {
                $items = collect()
                    ->concat($student->portfolios->map(fn (Portfolio $item) => $this->formatSubmission('portfolio', $item, $student)))
                    ->concat($student->resumes->filter(fn (Resume $item) => filled($item->file_path))->map(fn (Resume $item) => $this->formatSubmission('resumes', $item, $student)))
                    ->sortByDesc('created_at')
                    ->values();

                return $this->formatStudent($student, $items);
            })
            ->filter(fn (array $student) => $student['items'] !== [])
            ->values();

        if ($request->expectsJson()) {
            return response()->json(['students' => $students]);
        }

        $programs = $coordinator->scopedStudentsQuery()
            ->whereNotNull('program')
            ->where('program', '<>', '')
            ->distinct()
            ->orderBy('program')
            ->pluck('program');

        return view('coordinator.verification.index', [
            'students' => $students,
            'programs' => $programs,
            'verificationBaseUrl' => route('coordinator.verification.index', [], false),
        ]);
    }

    public function updateStatus(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->findSubmission($type, $id);
        abort_if($model instanceof Resume, 403, 'Resumes cannot be reviewed by coordinators.');
        abort_if($model instanceof Certificate, 403, 'Certificates can no longer be reviewed by coordinators.');
        abort_if($model instanceof StudentCompetency, 403, 'Competencies can no longer be reviewed by coordinators.');
        abort_if(
            $model instanceof Portfolio
                && ! in_array($model->category, [
                    PortfolioCategory::Certificate,
                    PortfolioCategory::Project,
                    PortfolioCategory::DeploymentDocument,
                ], true),
            403,
            'This file category cannot be reviewed by coordinators.'
        );
        $deploymentDocument = $model instanceof Portfolio
            && $model->category === PortfolioCategory::DeploymentDocument;
        $allowedStatuses = $deploymentDocument
            ? ['submitted', 'approved', 'rejected']
            : ['pending', 'verified', 'rejected'];
        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'note' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
        ]);

        $this->authorizeStudent($model->student);
        $status = $deploymentDocument && $validated['status'] === 'pending'
            ? 'submitted'
            : $validated['status'];
        $change = $this->changeStatus($type, $model, $status, $validated['note'] ?? null);

        return response()->json([
            'item' => $change['item'],
            'message' => $this->actionMessage($validated['status']),
        ]);
    }

    public function verifyAll(Request $request, Student $student): JsonResponse
    {
        $coordinator = $this->coordinator();
        abort_unless($coordinator->canAccessStudent($student), 403, 'You cannot review evidence for this student.');

        $student->loadMissing(['portfolios']);
        $pending = collect()
            ->concat($student->portfolios->filter(fn (Portfolio $item) => in_array($item->category, [PortfolioCategory::Certificate, PortfolioCategory::Project], true) && $this->normalizeStatus($item->verification_status) === 'pending')->map(fn ($item) => ['type' => 'portfolio', 'item' => $item]));

        $changes = DB::transaction(function () use ($pending) {
            return $pending->map(function (array $submission) {
                return $this->changeStatusInTransaction(
                    $submission['type'],
                    $submission['item'],
                    'verified',
                    null
                );
            });
        });

        $this->sendStatusNotifications($changes, $student);

        return response()->json([
            'items' => $changes->pluck('item')->values(),
            'message' => $changes->count().' verified',
        ]);
    }

    public function studentDetails(Student $student): JsonResponse
    {
        $coordinator = $this->coordinator();
        abort_unless($coordinator->canAccessStudent($student), 403, 'You cannot view this student.');

        $student->load([
            'user:id,name,email',
            'portfolios.reviewerUser:id,name',
        ]);

        $items = collect()
            ->concat($student->portfolios->map(fn (Portfolio $item) => $this->formatSubmission('portfolio', $item, $student)))
            ->sortByDesc('created_at')
            ->values();

        $activity = VerificationActivity::query()
            ->where('student_id', $student->id)
            ->with('coordinator.user:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (VerificationActivity $entry) => [
                'item' => $entry->item,
                'action' => $entry->action,
                'note' => $entry->note,
                'who' => $entry->coordinator?->user?->name ?? 'Coordinator',
                'created_at' => $entry->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'deployment_document_upload_url' => route('coordinator.students.deployment-documents.store', $student),
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'student_id_number' => $student->student_id_number,
                'program' => $student->program,
                'department' => $student->department,
                'year_level' => $student->year_level,
                'preferred_internship_field' => $student->preferred_internship_field,
                'preferred_work_setup' => $student->preferred_work_setup,
                'preferred_location' => $student->preferred_location,
                'initials' => $this->initials($student->user->name),
            ],
            'items' => $items,
            'activity' => $activity,
            'progress' => $this->progressFor($items),
            'portfolio_stats' => $this->portfolioStats($student),
            'coordinator_upload_requirements' => DeploymentRequirement::active()
                ->where('upload_source', DeploymentRequirement::SOURCE_COORDINATOR)
                ->orderBy('sort_order')
                ->get(['id', 'name']),
        ]);
    }

    public function showEvidence(string $type, int $id): Response
    {
        $model = $this->findSubmission($type, $id);
        $this->authorizeStudent($model->student);
        $path = $this->evidencePath($type, $model);

        abort_unless($path, 404, 'Evidence file not found.');

        $disk = $this->diskWithFile($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['doc', 'docx'], true)) {
            return Storage::disk($disk)->download($path, basename($path), [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        abort_unless(isset($mimeTypes[$extension]), 404, 'This file type cannot be previewed.');

        return Storage::disk($disk)->response($path, null, [
            'Content-Type' => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadEvidence(string $type, int $id): Response
    {
        $model = $this->findSubmission($type, $id);
        $this->authorizeStudent($model->student);
        $path = $this->evidencePath($type, $model);
        abort_unless($path, 404, 'Evidence file not found.');
        $disk = $this->diskWithFile($path);

        return Storage::disk($disk)->download($path, basename($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reviewPortfolio(Request $request, Portfolio $portfolio): RedirectResponse
    {
        return $this->legacyReview($request, 'portfolio', $portfolio);
    }

    private function legacyReview(Request $request, string $type, Model $model): RedirectResponse
    {
        abort_if($model instanceof Resume, 403, 'Resumes cannot be reviewed by coordinators.');
        abort_if(
            $model instanceof Portfolio
                && ! in_array($model->category, [
                    PortfolioCategory::Certificate,
                    PortfolioCategory::Project,
                    PortfolioCategory::DeploymentDocument,
                ], true),
            403,
            'This file category cannot be reviewed by coordinators.'
        );
        $deploymentDocument = $model instanceof Portfolio
            && $model->category === PortfolioCategory::DeploymentDocument;
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in($deploymentDocument ? ['approved', 'rejected'] : ['verified', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:2000', 'required_if:verification_status,rejected'],
        ]);

        $this->authorizeStudent($model->student);
        $this->changeStatus($type, $model, $validated['verification_status'], $validated['review_notes'] ?? null);

        return back()->with('success', ucfirst(rtrim($type, 's')).' evidence updated.');
    }

    private function changeStatus(string $type, Model $model, string $status, ?string $note): array
    {
        $change = DB::transaction(fn () => $this->changeStatusInTransaction($type, $model, $status, $note));
        $this->sendStatusNotifications(collect([$change]), $model->student);

        return $change;
    }

    private function changeStatusInTransaction(string $type, Model $model, string $status, ?string $note): array
    {
        $student = $model->student;
        $coordinator = $this->coordinator();
        abort_unless($coordinator->canAccessStudent($student), 403, 'You cannot review evidence for this student.');

        $oldStatus = $this->normalizeStatus($model->verification_status);
        if ($oldStatus === $status) {
            return [
                'item' => $this->formatSubmission($type, $model, $student),
                'changed' => false,
                'status' => $status,
                'type' => $type,
                'model' => $model,
            ];
        }

        $deploymentDocument = $model instanceof Portfolio
            && $model->category === PortfolioCategory::DeploymentDocument;
        $resetStatus = in_array($status, ['pending', 'submitted'], true);
        $reviewerMetadata = $model instanceof Certificate || $model instanceof Portfolio || $model instanceof Resume
            ? ['reviewed_by_user_id' => $resetStatus ? null : $coordinator->user_id]
            : [];
        $model->forceFill([
            'verification_status' => match ($status) {
                'pending' => 'evidence_submitted',
                default => $status,
            },
            'review_notes' => $resetStatus ? null : $note,
            'reviewed_at' => $resetStatus ? null : now(),
            ...$reviewerMetadata,
        ])->save();

        VerificationActivity::create([
            'student_id' => $student->id,
            'item_type' => $type,
            'item_id' => $model->id,
            'item' => $this->submissionTitle($type, $model),
            'action' => $status,
            'note' => $note,
            'coordinator_id' => $coordinator->id,
        ]);
        $student->calculateProfileCompletion();

        return [
            'item' => $this->formatSubmission($type, $model->fresh(), $student),
            'changed' => true,
            'status' => $status,
            'type' => $type,
            'model' => $model,
        ];
    }

    private function sendStatusNotifications(Collection $changes, Student $student): void
    {
        foreach ($changes->where('changed', true)->whereIn('status', ['verified', 'approved', 'rejected']) as $change) {
            $isApproved = in_array($change['status'], ['verified', 'approved'], true);
            $title = $isApproved
                ? ($change['status'] === 'approved' ? 'Document Approved' : 'Evidence Verified')
                : 'Evidence Rejected';
            $itemTitle = $this->submissionTitle($change['type'], $change['model']);

            $this->notificationService->send(
                $student->user,
                'verification',
                $this->submissionTypeLabel($change['type']).' '.$title,
                $isApproved
                    ? 'Your submission "'.$itemTitle.'" has been reviewed by the coordinator.'
                    : 'Your submission "'.$itemTitle.'" was rejected by the coordinator. Note: '.$change['model']->review_notes,
                [
                    'verification_type' => $change['type'],
                    'item_name' => $itemTitle,
                    'status' => $change['status'],
                    'review_notes' => $change['model']->review_notes,
                ]
            );
        }
    }

    private function formatSubmission(string $type, Model $item, ?Student $student = null): array
    {
        $student ??= $item->student;

        return [
            'id' => $item->id,
            'student_id' => $student->id,
            'type' => $type,
            'category' => method_exists($item, 'getAttribute') ? $item->getAttribute('category')?->value : null,
            'title' => $this->submissionTitle($type, $item),
            'subtype' => $this->submissionSubtype($type, $item),
            'status' => $item instanceof Resume ? 'verified' : $this->normalizeStatus($item->verification_status),
            'status_label' => $item instanceof Resume
                ? 'Generated'
                : ($item->verification_status === 'resubmitted' ? 'Resubmitted' : 'Submitted'),
            'reviewer_note' => $item->review_notes,
            'reviewed_by_user_id' => $item->reviewed_by_user_id,
            'reviewer_name' => method_exists($item, 'reviewerUser') ? $item->reviewerUser?->name : null,
            'reviewed_at' => $item->reviewed_at?->toIso8601String(),
            'created_at' => $item->created_at?->toIso8601String(),
            'submitted' => $item->created_at?->diffForHumans(),
            'has_evidence' => (bool) $this->evidencePath($type, $item),
            'extension' => strtolower(pathinfo((string) $this->evidencePath($type, $item), PATHINFO_EXTENSION)),
            'file_size' => $this->fileSize($this->evidencePath($type, $item)),
        ];
    }

    private function formatStudent(Student $student, Collection $items): array
    {
        return [
            'id' => $student->id,
            'name' => $student->user->name,
            'email' => $student->user->email,
            'student_id_number' => $student->student_id_number,
            'program' => $student->program,
            'department' => $student->department,
            'year_level' => $student->year_level,
            'preferred_internship_field' => $student->preferred_internship_field,
            'preferred_work_setup' => $student->preferred_work_setup,
            'preferred_location' => $student->preferred_location,
            'initials' => $this->initials($student->user->name),
            'items' => $items->all(),
            'progress' => $this->progressFor($items),
        ];
    }

    private function progressFor(Collection $items): array
    {
        $reviewable = $items->reject(fn (array $item) => $item['category'] === PortfolioCategory::Resume->value);
        $total = $reviewable->count();
        $verified = $reviewable->whereIn('status', ['verified', 'approved'])->count();

        return [
            'verified' => $verified,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($verified / $total) * 100) : 0,
            'pending' => $reviewable->where('status', 'pending')->count(),
        ];
    }

    private function portfolioStats(Student $student): array
    {
        $certificatePaths = $student->certificates
            ->filter(fn (Certificate $certificate) => filled($certificate->file_path))
            ->map(fn (Certificate $certificate) => $certificate->title.'|'.$certificate->file_path)
            ->flip();
        $items = $student->portfolios
            ->reject(fn (Portfolio $portfolio) => $portfolio->category === PortfolioCategory::Certificate
                && $certificatePaths->has($portfolio->title.'|'.$portfolio->file_path))
            ->concat($student->certificates)
            ->concat($student->resumes->filter(fn (Resume $resume) => filled($resume->file_path)));
        $paths = $student->portfolios->pluck('file_path')
            ->merge($student->certificates->pluck('file_path'))
            ->merge($student->resumes->pluck('file_path'))
            ->filter()
            ->unique();
        $bytes = $paths->sum(function (string $path): int {
            foreach (UploadDisks::search() as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->size($path);
                }
            }

            return 0;
        });

        return [
            'total' => $items->count(),
            'certificates' => $items->filter(fn ($item) => $item instanceof Certificate
                || ($item instanceof Portfolio && $item->category === PortfolioCategory::Certificate))->count(),
            'projects' => $items->filter(fn ($item) => $item instanceof Portfolio
                && $item->category === PortfolioCategory::Project)->count(),
            'storage_used' => number_format($bytes / (1024 * 1024), 2).' MB',
        ];
    }

    private function findSubmission(string $type, int $id): Model
    {
        // 'certificates' is intentionally absent from the reviewable types used by
        // index/studentDetails/verifyAll/updateStatus (and from the 'verification.update'
        // route's type constraint) — coordinators can no longer review certificates.
        // It's kept here only so showEvidence/downloadEvidence can still resolve one for
        // read-only preview/download.
        $model = match ($type) {
            'competencies' => StudentCompetency::class,
            'certificates' => Certificate::class,
            'portfolio' => Portfolio::class,
            'resumes' => Resume::class,
            default => abort(404),
        };

        return $model::with('student.user')->findOrFail($id);
    }

    private function submissionTitle(string $type, Model $item): string
    {
        return match ($type) {
            'competencies' => $item->name,
            'resumes' => 'Resume',
            default => $item->title,
        };
    }

    private function submissionSubtype(string $type, Model $item): string
    {
        return match ($type) {
            'competencies' => $item->category?->label() ?? 'Competency',
            'resumes' => 'Resume',
            'portfolio' => $item->type?->label() ?? 'Portfolio',
        };
    }

    private function submissionTypeLabel(string $type): string
    {
        return match ($type) {
            'competencies' => 'Competency',
            'portfolio' => 'Portfolio',
        };
    }

    private function evidencePath(string $type, Model $item): ?string
    {
        return match ($type) {
            'competencies' => $item->evidence_path,
            'certificates', 'portfolio' => $item->file_path,
            'resumes' => $item->file_path,
            default => null,
        };
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'verified', 'rejected' => $status,
            'approved' => 'verified',
            default => 'pending',
        };
    }

    private function actionMessage(string $status): string
    {
        return match ($status) {
            'verified', 'approved' => $status === 'approved' ? 'Document approved' : '1 verified',
            'rejected' => 'Rejected with reason',
            default => 'Moved back to pending',
        };
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->implode('');
    }

    private function coordinator(): Coordinator
    {
        $coordinator = Auth::user()?->coordinator;
        abort_unless($coordinator && $coordinator->institution_id, 403, 'No institution has been assigned to this coordinator.');

        return $coordinator;
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless($this->coordinator()->canAccessStudent($student), 403, 'You cannot review evidence for this student.');
    }

    private function diskWithFile(string $path): string
    {
        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        abort(404, 'Evidence file not found.');
    }

    private function fileSize(?string $path): string
    {
        if (! $path) {
            return 'Unavailable';
        }

        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return number_format(Storage::disk($disk)->size($path) / 1024, 1).' KB';
            }
        }

        return 'Unavailable';
    }
}
