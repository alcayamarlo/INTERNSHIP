<?php

namespace App\Http\Controllers\Student;

use App\Enums\PortfolioType;
use App\Enums\PortfolioCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\StoreCertificateRequest;
use App\Http\Requests\Portfolio\StorePortfolioRequest;
use App\Http\Requests\Portfolio\UpdateCertificateRequest;
use App\Http\Requests\Portfolio\UpdatePortfolioRequest;
use App\Models\Certificate;
use App\Models\DeploymentRequirement;
use App\Models\Portfolio;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;

class PortfolioController extends Controller
{
    public function __construct(
        private FileUploadService $fileUpload,
        private ActivityLogService $activityLog
    ) {}

    protected function getStudentProfile(): Student
    {
        $user = Auth::user();

        $student = $user?->student;

        if (! $student) {
            $student = Student::create([
                'user_id' => $user->id,
                'institution_id' => null,
                'profile_completion' => 0,
            ]);
            $student->calculateProfileCompletion();
        }

        return $student;
    }

    public function index(Request $request)
    {
        $query = $request->getQueryString();
        $url = route('student.profile.edit').($query ? '?'.$query : '').'#portfolio';

        return redirect()->to($url);
    }

    public function create()
    {
        return view('student.portfolio.create', ['types' => PortfolioType::cases()]);
    }

    public function storePortfolio(StorePortfolioRequest $request)
    {
        $student = $this->getStudentProfile();
        $validated = $request->validated();

        $path = $this->fileUpload->upload(
            $request->file('file'),
            'portfolios/'.$student->id,
            (string) $student->id
        );

        try {
            $portfolio = DB::transaction(function () use ($student, $validated, $path) {
                $portfolio = Portfolio::create([
                    'student_id' => $student->id,
                    'title' => $validated['title'],
                    'type' => $validated['type'],
                    'category' => $this->categoryForType($validated['type']),
                    'description' => $validated['description'] ?? null,
                    'file_path' => $path,
                    'verification_status' => $this->categoryForType($validated['type']) === PortfolioCategory::DeploymentDocument
                        ? 'submitted'
                        : 'evidence_submitted',
                    'uploaded_by_user_id' => Auth::id(),
                ]);

                $this->activityLog->log(Auth::user(), 'portfolio_upload', ['portfolio_id' => $portfolio->id]);

                return $portfolio;
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Portfolio item uploaded successfully.']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Portfolio item uploaded successfully!');
    }

    public function storeDeploymentRequirement(Request $request, DeploymentRequirement $deploymentRequirement)
    {
        abort_unless(
            $deploymentRequirement->is_active
                && $deploymentRequirement->upload_source === DeploymentRequirement::SOURCE_STUDENT,
            404
        );

        $request->validate([
            'file' => FileUploadService::documentRules(),
        ]);

        $student = $this->getStudentProfile();
        $existing = $student->deploymentDocuments()
            ->where('deployment_requirement_id', $deploymentRequirement->id)
            ->latest('updated_at')
            ->first();
        $oldPath = $existing?->file_path;
        $path = $this->fileUpload->upload(
            $request->file('file'),
            'portfolios/'.$student->id.'/deployment',
            'student_'.$student->id
        );

        try {
            DB::transaction(function () use ($student, $deploymentRequirement, $existing, $path, $oldPath): void {
                $portfolio = $existing ?? new Portfolio([
                    'student_id' => $student->id,
                    'type' => 'transcript',
                    'category' => PortfolioCategory::DeploymentDocument,
                    'deployment_requirement_id' => $deploymentRequirement->id,
                ]);
                $portfolio->fill([
                    'title' => $deploymentRequirement->name,
                    'description' => 'Deployment requirement document.',
                    'file_path' => $path,
                    'verification_status' => $oldPath ? 'resubmitted' : 'submitted',
                    'review_notes' => null,
                    'reviewed_at' => null,
                    'reviewed_by_user_id' => null,
                    'uploaded_by_user_id' => Auth::id(),
                ])->save();
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            $student->calculateProfileCompletion();
            throw $exception;
        }

        $this->fileUpload->deleteIfUnreferenced($oldPath);
        $student->calculateProfileCompletion();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $deploymentRequirement->name.' submitted successfully.']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', $deploymentRequirement->name.' submitted successfully.');
    }

    public function bulkStore(StorePortfolioRequest $request)
    {
        $this->authorize('create', Portfolio::class);

        $items = $request->input('items', []);

        if (! is_array($items) || empty($items)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please upload at least one portfolio item.'], 422);
            }

            return back()->withErrors(['items' => 'Please upload at least one portfolio item.']);
        }

        $student = $this->getStudentProfile();
        $created = 0;
        $uploadedPaths = [];

        try {
            DB::transaction(function () use ($items, $request, $student, &$created, &$uploadedPaths) {
                foreach ($items as $index => $item) {
                    $file = $request->file('items.'.$index.'.file');

                    if (empty($item['title']) || empty($item['type']) || ! $file) {
                        continue;
                    }

                    $path = $this->fileUpload->upload(
                        $file,
                        'portfolios/'.$student->id,
                        (string) $student->id.'_'.$created
                    );
                    $uploadedPaths[] = $path;

                    Portfolio::create([
                        'student_id' => $student->id,
                        'title' => $item['title'],
                        'type' => $item['type'],
                        'category' => $this->categoryForType($item['type']),
                        'description' => $item['description'] ?? null,
                        'file_path' => $path,
                        'verification_status' => $this->categoryForType($item['type']) === PortfolioCategory::DeploymentDocument
                            ? 'submitted'
                            : 'evidence_submitted',
                        'uploaded_by_user_id' => Auth::id(),
                    ]);

                    $created++;
                }
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                $this->fileUpload->delete($path);
            }

            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $created.' portfolio items uploaded successfully.']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', $created.' portfolio items uploaded successfully!');
    }

    public function storeCertificate(StoreCertificateRequest $request)
    {
        $student = $this->getStudentProfile();
        $validated = $request->validated();

        $path = $this->fileUpload->upload(
            $request->file('file'),
            'certificates/'.$student->id,
            $student->id.'_cert'
        );

        try {
            DB::transaction(function () use ($student, $validated, $path) {
                Certificate::create([
                    'student_id' => $student->id,
                    'title' => $validated['title'],
                    'issuer' => $validated['issuer'] ?? null,
                    'issue_date' => $validated['issue_date'] ?? null,
                    'expiration_date' => $validated['expiration_date'] ?? null,
                    'category' => PortfolioCategory::Certificate,
                    'uploaded_by_user_id' => Auth::id(),
                    'verification_status' => 'evidence_submitted',
                    'file_path' => $path,
                ]);

                Portfolio::create([
                    'student_id' => $student->id,
                    'title' => $validated['title'],
                    'type' => 'certificate',
                    'category' => PortfolioCategory::Certificate,
                    'description' => 'Issued by '.($validated['issuer'] ?? 'Unknown'),
                    'file_path' => $path,
                    'uploaded_by_user_id' => Auth::id(),
                ]);

                $this->activityLog->log(Auth::user(), 'certificate_upload', ['student_id' => $student->id]);
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Certificate uploaded successfully.']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Certificate uploaded successfully!');
    }

    public function editCertificate(Certificate $certificate)
    {
        $this->authorize('update', $certificate);

        return view('student.certificates.edit', compact('certificate'));
    }

    public function updateCertificate(UpdateCertificateRequest $request, Certificate $certificate)
    {
        $this->authorize('update', $certificate);

        $validated = $request->validated();
        $previousPath = $certificate->file_path;
        $newPath = null;

        if ($request->hasFile('file')) {
            $newPath = $this->fileUpload->upload(
                $request->file('file'),
                'certificates/'.$certificate->student_id,
                $certificate->student_id.'_cert'
            );
        }

        try {
            DB::transaction(function () use ($certificate, $validated, $newPath, $previousPath) {
                $updates = collect($validated)->except('file')->all();
                $updates['verification_status'] = 'evidence_submitted';
                $updates['review_notes'] = null;
                $updates['reviewed_at'] = null;
                $updates['reviewed_by_user_id'] = null;
                if ($newPath) {
                    $updates['file_path'] = $newPath;
                    $updates['uploaded_by_user_id'] = Auth::id();
                }
                $certificate->update($updates);

                Portfolio::query()
                    ->where('student_id', $certificate->student_id)
                    ->where('file_path', $previousPath)
                    ->where('category', PortfolioCategory::Certificate)
                    ->update([
                        'title' => $validated['title'],
                        'description' => 'Issued by '.($validated['issuer'] ?? 'Unknown'),
                        'verification_status' => 'evidence_submitted',
                        'review_notes' => null,
                        'reviewed_at' => null,
                        'reviewed_by_user_id' => null,
                        ...($newPath ? [
                            'file_path' => $newPath,
                            'uploaded_by_user_id' => Auth::id(),
                        ] : []),
                    ]);

                $this->activityLog->log(Auth::user(), 'certificate_update', ['certificate_id' => $certificate->id]);
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

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Certificate updated.']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Certificate updated.');
    }

    public function show(Portfolio $portfolio)
    {
        $this->authorize('view', $portfolio);

        return view('student.portfolio.show', compact('portfolio'));
    }

    public function edit(Portfolio $portfolio)
    {
        $this->authorize('update', $portfolio);

        return view('student.portfolio.edit', [
            'portfolio' => $portfolio,
            'types' => PortfolioType::cases(),
        ]);
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio)
    {
        $this->authorize('update', $portfolio);

        $validated = $request->validated();

        $previousPath = $portfolio->file_path;
        $newPath = null;

        if ($request->hasFile('file')) {
            $newPath = $this->fileUpload->upload(
                $request->file('file'),
                'portfolios/'.$portfolio->student_id,
                (string) $portfolio->student_id
            );
            $validated['file_path'] = $newPath;
        }

        try {
            DB::transaction(function () use ($portfolio, $validated, $newPath, $previousPath) {
                $updates = collect($validated)->except('file')->all();
                $updates['category'] = $this->categoryForType($updates['type'] ?? $portfolio->type->value);

                if ($newPath) {
                    $category = $this->categoryForType($updates['type'] ?? $portfolio->type->value);
                    $updates['verification_status'] = $previousPath
                        ? 'resubmitted'
                        : ($category === PortfolioCategory::DeploymentDocument ? 'submitted' : 'evidence_submitted');
                    $updates['uploaded_by_user_id'] = Auth::id();
                    $updates['review_notes'] = null;
                    $updates['reviewed_at'] = null;
                    $updates['reviewed_by_user_id'] = null;
                }

                $portfolio->update($updates);

                $this->activityLog->log(Auth::user(), 'portfolio_update', ['portfolio_id' => $portfolio->id]);
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Portfolio item updated successfully!',
                'redirect_url' => route('student.profile.edit').'#portfolio',
            ]);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Portfolio item updated successfully!');
    }

    public function destroyPortfolio(Portfolio $portfolio)
    {
        $this->authorize('delete', $portfolio);

        $portfolioId = $portfolio->id;
        $path = $portfolio->file_path;

        DB::transaction(function () use ($portfolio, $portfolioId) {
            $portfolio->delete();
            $this->activityLog->log(Auth::user(), 'portfolio_delete', ['portfolio_id' => $portfolioId]);
        });

        $this->fileUpload->deleteIfUnreferenced($path);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Portfolio item deleted successfully!']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Portfolio item deleted successfully!');
    }

    public function destroyCertificate(Certificate $certificate)
    {
        $this->authorize('delete', $certificate);

        $path = $certificate->file_path;

        DB::transaction(function () use ($certificate) {
            $certificate->delete();
            $this->activityLog->log(Auth::user(), 'certificate_delete', ['certificate_id' => $certificate->id]);
        });

        $this->fileUpload->deleteIfUnreferenced($path);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Certificate deleted successfully!']);
        }

        return redirect()->to(route('student.profile.edit').'#portfolio')
            ->with('success', 'Certificate deleted successfully!');
    }

    public function download(Portfolio $portfolio)
    {
        $this->authorize('download', $portfolio);
        $disk = $this->diskWithFile($portfolio->file_path);

        return Storage::disk($disk)->download(
            $portfolio->file_path,
            $portfolio->title.'.'.pathinfo($portfolio->file_path, PATHINFO_EXTENSION)
        );
    }

    public function preview(Portfolio $portfolio)
    {
        $this->authorize('preview', $portfolio);
        $disk = $this->diskWithFile($portfolio->file_path);

        $extension = pathinfo($portfolio->file_path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        abort_unless(isset($mimeTypes[strtolower($extension)]), 404, 'This file type cannot be previewed.');

        return Storage::disk($disk)->response($portfolio->file_path, null, [
            'Content-Type' => $mimeTypes[strtolower($extension)] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$portfolio->title.'.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function diskWithFile(?string $path): string
    {
        abort_unless($path, 404, 'File not found.');

        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        abort(404, 'File not found.');
    }

    private function categoryForType(string $type): PortfolioCategory
    {
        return match ($type) {
            PortfolioType::Certificate->value, PortfolioType::Award->value => PortfolioCategory::Certificate,
            PortfolioType::Resume->value => PortfolioCategory::Resume,
            PortfolioType::Transcript->value => PortfolioCategory::DeploymentDocument,
            default => PortfolioCategory::Project,
        };
    }

}
