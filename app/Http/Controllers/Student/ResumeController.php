<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\BuildResume;
use App\Models\Resume;
use App\Services\ResumeBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function __construct(private ResumeBuilderService $resumeBuilder) {}

    public function index()
    {
        return view('student.resume.index', [
            'resumes' => Auth::user()->student->resumes()->latest()->paginate(10),
        ]);
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
}
