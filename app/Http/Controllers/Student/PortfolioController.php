<?php

namespace App\Http\Controllers\Student;

use App\Enums\PortfolioType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\StoreCertificateRequest;
use App\Http\Requests\Portfolio\StorePortfolioRequest;
use App\Http\Requests\Portfolio\UpdatePortfolioRequest;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        }

        return $student;
    }

    public function index(Request $request)
    {
        $student = $this->getStudentProfile();
        $query = Portfolio::where('student_id', $student->id);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $sortBy = in_array($request->get('sort_by'), ['title', 'type', 'created_at'], true) ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $portfolios = $query->paginate(12);

        return view('student.portfolio.index', [
            'portfolios' => $portfolios,
            'certificates' => $student->certificates()->latest()->get(),
            'types' => PortfolioType::cases(),
            'stats' => [
                'total' => Portfolio::where('student_id', $student->id)->count(),
                'certificates' => Portfolio::where('student_id', $student->id)->where('type', 'certificate')->count(),
                'projects' => Portfolio::where('student_id', $student->id)->where('type', 'project')->count(),
                'storage_usage' => $this->calculateStorageUsage($student),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'type' => $request->type ?? '',
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
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
                    'description' => $validated['description'] ?? null,
                    'file_path' => $path,
                ]);

                $this->activityLog->log(Auth::user(), 'portfolio_upload', ['portfolio_id' => $portfolio->id]);

                return $portfolio;
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Portfolio item uploaded successfully!');
    }

    public function bulkStore(StorePortfolioRequest $request)
    {
        $this->authorize('create', Portfolio::class);

        $items = $request->input('items', []);

        if (! is_array($items) || empty($items)) {
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
                        'description' => $item['description'] ?? null,
                        'file_path' => $path,
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

        return redirect()->route('student.portfolio.index')
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
                    'verification_status' => 'evidence_submitted',
                    'file_path' => $path,
                ]);

                Portfolio::create([
                    'student_id' => $student->id,
                    'title' => $validated['title'],
                    'type' => 'certificate',
                    'description' => 'Issued by '.($validated['issuer'] ?? 'Unknown'),
                    'file_path' => $path,
                ]);

                $this->activityLog->log(Auth::user(), 'certificate_upload', ['student_id' => $student->id]);
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($path);
            throw $exception;
        }

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Certificate uploaded successfully!');
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
            DB::transaction(function () use ($portfolio, $validated) {
                $portfolio->update(collect($validated)->except('file')->all());

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

        return redirect()->route('student.portfolio.index')
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

        return redirect()->route('student.portfolio.index')
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

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Certificate deleted successfully!');
    }

    public function download(Portfolio $portfolio)
    {
        $this->authorize('download', $portfolio);

        if (! Storage::disk('public')->exists($portfolio->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $portfolio->file_path,
            $portfolio->title.'.'.pathinfo($portfolio->file_path, PATHINFO_EXTENSION)
        );
    }

    public function preview(Portfolio $portfolio)
    {
        $this->authorize('preview', $portfolio);

        if (! Storage::disk('public')->exists($portfolio->file_path)) {
            abort(404, 'File not found.');
        }

        $extension = pathinfo($portfolio->file_path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        return Storage::disk('public')->response($portfolio->file_path, null, [
            'Content-Type' => $mimeTypes[strtolower($extension)] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$portfolio->title.'.'.$extension.'"',
        ]);
    }

    private function calculateStorageUsage($student): string
    {
        $totalBytes = Portfolio::where('student_id', $student->id)
            ->get()
            ->sum(fn ($portfolio) => Storage::disk('public')->exists($portfolio->file_path)
                ? Storage::disk('public')->size($portfolio->file_path)
                : 0);

        return round($totalBytes / (1024 * 1024), 2).' MB';
    }
}
