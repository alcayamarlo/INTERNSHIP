<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\BuildResume;
use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Models\Student;
use App\Services\ResumeBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function __construct(private ResumeBuilderService $resumeBuilder) {}

    public function index()
    {
        $student = Auth::user()->student;
        $view = request()->query('view', 'active') === 'archived' ? 'archived' : 'active';
        $query = $student->resumes();

        if ($view === 'archived') {
            $query->withArchived()->whereNotNull('archived_at');
        }

        $resumes = $query->latest()->paginate(10)->appends(['view' => $view]);
        $resumes->getCollection()->each(
            fn (Resume $resume) => $resume->setAttribute(
                'active_application_count',
                $this->activeApplicationCount($student->id, $resume)
            )
        );

        return view('student.resume.index', [
            'resumes' => $resumes,
            'view' => $view,
        ]);
    }

    public function archive(Request $request, int $resume)
    {
        $resume = Resume::withArchived()->findOrFail($resume);
        $this->authorize('archive', $resume);

        $activeApplicationCount = $this->activeApplicationCount($resume->student_id, $resume);
        if ($activeApplicationCount > 0 && ! $request->boolean('confirmed_active_applications')) {
            $message = "This resume is attached to {$activeApplicationCount} active application(s). Employers reviewing those applications will still be able to view it. Archive anyway?";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'confirmation_required' => true,
                    'active_application_count' => $activeApplicationCount,
                    'message' => $message,
                ], 409);
            }

            return back()->withErrors(['archive' => 'Please confirm archiving this resume while it is attached to active applications.']);
        }

        $resume->archive();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Resume archived.',
            ]);
        }

        return back()->with('success', 'Resume archived.');
    }

    public function restore(Request $request, int $resume)
    {
        $resume = Resume::withArchived()->findOrFail($resume);
        $this->authorize('restore', $resume);
        $resume->restore();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Resume restored.',
            ]);
        }

        return back()->with('success', 'Resume restored.');
    }

    public function generate()
    {
        BuildResume::dispatchSync(Auth::user()->student->id);

        return redirect()->route('student.resume.index')->with(
            'success',
            'Resume generated successfully.'
        );
    }

    public function download(Resume $resume)
    {
        $this->authorize('download', $resume);

        return Storage::disk($this->diskWithFile($resume->file_path))->download(
            $resume->file_path,
            'resume-'.$resume->student_id.'.pdf',
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']
        );
    }

    public function view(Resume $resume)
    {
        $this->authorize('view', $resume);

        return Storage::disk($this->diskWithFile($resume->file_path))->response($resume->file_path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeResume(Resume $resume): void
    {
        $this->authorize('view', $resume);
        $this->diskWithFile($resume->file_path);
    }

    private function diskWithFile(?string $path): string
    {
        abort_unless($path, 404, 'Resume file not found. Please regenerate it.');

        foreach (['private', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        abort(404, 'Resume file not found. Please regenerate it.');
    }

    private function activeApplicationCount(int $studentId, ?Resume $resume = null): int
    {
        if ($resume) {
            $latestResumeId = Resume::withArchived()
                ->where('student_id', $studentId)
                ->latest()
                ->value('id');

            if ((int) $latestResumeId !== (int) $resume->id) {
                return 0;
            }
        }

        return Student::findOrFail($studentId)->applications()
            ->whereIn('status', array_map(
                fn (ApplicationStatus $status) => $status->value,
                array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status) => $status->isActive())
            ))
            ->count();
    }
}
