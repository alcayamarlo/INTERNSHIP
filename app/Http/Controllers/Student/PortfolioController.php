<?php

namespace App\Http\Controllers\Student;

use App\Enums\PortfolioType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\StoreCertificateRequest;
use App\Http\Requests\Portfolio\StorePortfolioRequest;
use App\Http\Requests\Portfolio\UpdatePortfolioRequest;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function __construct(
        private FileUploadService $fileUpload,
        private ActivityLogService $activityLog
    ) {}

    public function index(Request $request)
    {
        $student = Auth::user()->student;
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

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $portfolios = $query->paginate(12);

        return view('student.portfolio.index', [
            'portfolios' => $portfolios,
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
        $student = Auth::user()->student;
        $validated = $request->validated();

        $path = $this->fileUpload->upload(
            $request->file('file'),
            'portfolios/'.$student->id,
            (string) $student->id
        );

        $portfolio = Portfolio::create([
            'student_id' => $student->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
        ]);

        $this->activityLog->log(Auth::user(), 'portfolio_upload', ['portfolio_id' => $portfolio->id]);

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Portfolio item uploaded successfully!');
    }

    public function storeCertificate(StoreCertificateRequest $request)
    {
        $student = Auth::user()->student;
        $validated = $request->validated();

        $path = $this->fileUpload->upload(
            $request->file('file'),
            'certificates/'.$student->id,
            $student->id.'_cert'
        );

        Certificate::create([
            'student_id' => $student->id,
            'title' => $validated['title'],
            'issuer' => $validated['issuer'] ?? null,
            'issue_date' => $validated['issue_date'] ?? null,
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

        if ($request->hasFile('file')) {
            $this->fileUpload->delete($portfolio->file_path);
            $validated['file_path'] = $this->fileUpload->upload(
                $request->file('file'),
                'portfolios/'.$portfolio->student_id,
                (string) $portfolio->student_id
            );
        }

        $portfolio->update(collect($validated)->except('file')->all());

        $this->activityLog->log(Auth::user(), 'portfolio_update', ['portfolio_id' => $portfolio->id]);

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Portfolio item updated successfully!');
    }

    public function destroyPortfolio(Portfolio $portfolio)
    {
        $this->authorize('delete', $portfolio);

        $portfolioId = $portfolio->id;
        $this->fileUpload->delete($portfolio->file_path);
        $portfolio->delete();

        $this->activityLog->log(Auth::user(), 'portfolio_delete', ['portfolio_id' => $portfolioId]);

        return redirect()->route('student.portfolio.index')
            ->with('success', 'Portfolio item deleted successfully!');
    }

    public function destroyCertificate(Certificate $certificate)
    {
        $this->authorize('delete', $certificate);

        $this->fileUpload->delete($certificate->file_path);

        Portfolio::where('student_id', $certificate->student_id)
            ->where('file_path', $certificate->file_path)
            ->delete();

        $certificate->delete();

        $this->activityLog->log(Auth::user(), 'certificate_delete', ['certificate_id' => $certificate->id]);

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
