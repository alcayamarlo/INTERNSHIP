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
        BuildResume::dispatch(Auth::user()->student->id);

        return back()->with('success', 'Resume is being generated. You will be notified when it is ready.');
    }

    public function download(Resume $resume)
    {
        $this->authorizeResume($resume);

        return response()->download(
            Storage::disk('public')->path($resume->file_path),
            'resume-'.$resume->student_id.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function view(Resume $resume)
    {
        $this->authorizeResume($resume);

        return response()->file(
            Storage::disk('public')->path($resume->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    private function authorizeResume(Resume $resume): void
    {
        abort_unless($resume->student_id === Auth::user()->student->id, 403);
        abort_unless($resume->file_path && Storage::disk('public')->exists($resume->file_path), 404, 'Resume file not found. Please regenerate it.');
    }
}
