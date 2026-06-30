<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        $resume = $this->resumeBuilder->build(Auth::user()->student);

        return back()->with('success', 'Resume generated successfully.')->with('download', $resume->id);
    }

    public function download(Resume $resume)
    {
        abort_unless($resume->student_id === Auth::user()->student->id, 403);

        return Storage::disk('public')->download($resume->file_path);
    }
}
