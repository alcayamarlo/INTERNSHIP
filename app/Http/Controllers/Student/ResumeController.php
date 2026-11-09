<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\BuildResume;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Services\ResumeBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function __construct(private ResumeBuilderService $resumeBuilder) {}

    public function index()
    {
        $student = Auth::user()->student;
        $resumes = $this->resumeBuilder->resumesFor($student);
        $linkedApplications = InternshipApplication::query()
            ->where('student_id', $student->id)
            ->whereIn('resume_id', $resumes->modelKeys())
            ->selectRaw('resume_id, count(*) as aggregate')
            ->groupBy('resume_id')
            ->pluck('aggregate', 'resume_id');

        return view('student.resume.index', [
            'resumes' => $resumes,
            'linkedApplications' => $linkedApplications,
            'maxResumes' => ResumeBuilderService::MAX_GENERATED_RESUMES,
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

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorize('delete', $resume);
        $this->resumeBuilder->delete($resume);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Resume deleted.']);
        }

        return redirect()->route('student.resume.index')->with('success', 'Resume deleted.');
    }
}
