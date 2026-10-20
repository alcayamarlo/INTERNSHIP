<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PortfolioCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStudentIdRequest;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\NotificationService;
use App\Support\UploadDisks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PortfolioFileController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload,
        private NotificationService $notificationService
    ) {}

    public function studentFiles(Student $student)
    {
        $certificates = $student->certificates()->with('reviewerUser:id,name')->get();
        $certificatePaths = $certificates
            ->filter(fn (Certificate $certificate) => filled($certificate->file_path))
            ->map(fn (Certificate $certificate) => $certificate->title.'|'.$certificate->file_path)
            ->flip();
        $files = $student->portfolios()
            ->with('reviewerUser:id,name')
            ->get()
            ->reject(fn (Portfolio $portfolio) => $portfolio->category === PortfolioCategory::Certificate
                && $certificatePaths->has($portfolio->title.'|'.$portfolio->file_path))
            ->concat($certificates)
            ->concat($student->resumes()->with('reviewerUser:id,name')->get()->filter(fn (Resume $resume) => filled($resume->file_path)))
            ->each(function (Model $file): void {
                if ($file instanceof Resume) {
                    $file->setAttribute('title', 'Resume · '.$file->generated_at?->format('M d, Y'));
                }
                $file->setAttribute('file_size_display', $this->fileSize($file->file_path));
            })
            ->sortByDesc('created_at')
            ->values();
        $paths = $files->pluck('file_path')->filter()->unique();
        $storageBytes = $paths->sum(function (string $path): int {
            foreach (UploadDisks::search() as $disk) {
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                    return \Illuminate\Support\Facades\Storage::disk($disk)->size($path);
                }
            }

            return 0;
        });

        return view('admin.portfolio-files.index', [
            'student' => $student->load('user'),
            'studentIdNeedsCorrection' => $student->studentIdNeedsCorrection(),
            'files' => $files,
            'stats' => [
                'total' => $files->count(),
                'certificates' => $files->filter(fn (Model $file) => $file instanceof Certificate
                    || ($file instanceof Portfolio && $file->category === PortfolioCategory::Certificate))->count(),
                'projects' => $files->filter(fn (Model $file) => $file instanceof Portfolio
                    && $file->category === PortfolioCategory::Project)->count(),
                'storage_used' => number_format($storageBytes / (1024 * 1024), 2).' MB',
            ],
        ]);
    }

    public function updateStudentId(UpdateStudentIdRequest $request, Student $student): RedirectResponse
    {
        $student->update([
            'student_id_number' => $request->validated('student_id_number'),
        ]);
        $this->activityLog->log(auth()->user(), 'admin_student_id_update', [
            'student_id' => $student->id,
        ]);

        return back()->with('success', 'Student ID updated.');
    }

    public function review(Request $request, string $type, int $id): RedirectResponse
    {
        $file = $this->findFile($type, $id);
        $category = $file->category;
        $allowedStatuses = match ($category) {
            PortfolioCategory::DeploymentDocument => ['submitted', 'approved', 'rejected'],
            PortfolioCategory::Resume => ['available', 'verified', 'rejected'],
            default => ['pending', 'verified', 'rejected'],
        };
        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'remark' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
        ]);

        DB::transaction(function () use ($file, $validated, $type): void {
            $file->forceFill([
                'verification_status' => match ($validated['status']) {
                    'pending' => 'evidence_submitted',
                    default => $validated['status'],
                },
                'review_notes' => in_array($validated['status'], ['pending', 'submitted', 'available'], true)
                    ? null
                    : ($validated['remark'] ?? null),
                'reviewed_at' => in_array($validated['status'], ['pending', 'submitted', 'available'], true)
                    ? null
                    : now(),
                'reviewed_by_user_id' => in_array($validated['status'], ['pending', 'submitted', 'available'], true)
                    ? null
                    : auth()->id(),
            ])->save();

            $this->activityLog->log(auth()->user(), 'portfolio_file_review', [
                'type' => $type,
                'file_id' => $file->id,
                'category' => $file->category->value,
                'status' => $validated['status'],
                'remark' => $validated['remark'] ?? null,
            ]);
        });

        if ($file->category === PortfolioCategory::Certificate
            && in_array($validated['status'], ['verified', 'rejected'], true)) {
            $this->notifyStudent($file, $validated['status'], $validated['remark'] ?? null);
        }

        if ($file->category === PortfolioCategory::DeploymentDocument
            && in_array($validated['status'], ['approved', 'rejected'], true)) {
            $this->notifyStudent($file, $validated['status'], $validated['remark'] ?? null);
        }

        return back()->with('success', 'File review updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $file = $this->findFile($type, $id);
        $path = $file->file_path;

        DB::transaction(function () use ($file, $type): void {
            if ($file instanceof Certificate) {
                Portfolio::query()
                    ->where('student_id', $file->student_id)
                    ->where('file_path', $file->file_path)
                    ->where('category', PortfolioCategory::Certificate->value)
                    ->delete();
            }
            $file->delete();
            $this->activityLog->log(auth()->user(), 'portfolio_file_remove', [
                'type' => $type,
                'file_id' => $file->id,
                'category' => $file->category->value,
                'student_id' => $file->student_id,
            ]);
        });

        $this->fileUpload->deleteIfUnreferenced($path);

        return back()->with('success', 'File removed.');
    }

    private function findFile(string $type, int $id): Model
    {
        $model = match ($type) {
            'portfolio' => Portfolio::class,
            'certificate' => Certificate::class,
            'resume' => Resume::class,
            default => abort(404),
        };

        return $model::with('student.user')->findOrFail($id);
    }

    private function notifyStudent(Model $file, string $status, ?string $remark): void
    {
        $approved = in_array($status, ['verified', 'approved'], true);
        $this->notificationService->send(
            $file->student->user,
            'verification',
            $file->category === PortfolioCategory::DeploymentDocument
                ? 'Document '.($approved ? 'Approved' : 'Rejected')
                : 'Certificate '.($approved ? 'Verified' : 'Rejected'),
            'Your file "'.$file->title.'" was '.($approved ? 'approved' : 'rejected').' by an administrator.'
                .($remark ? ' Remark: '.$remark : ''),
            [
                'file_id' => $file->id,
                'category' => $file->category->value,
                'status' => $status,
                'remark' => $remark,
            ]
        );
    }

    private function fileSize(?string $path): string
    {
        if (! $path) {
            return 'Unavailable';
        }
        foreach (UploadDisks::search() as $disk) {
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                return number_format(\Illuminate\Support\Facades\Storage::disk($disk)->size($path) / 1024, 1).' KB';
            }
        }

        return 'Unavailable';
    }
}
