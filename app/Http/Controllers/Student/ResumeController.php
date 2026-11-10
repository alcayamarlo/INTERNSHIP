<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\BuildResume;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Services\ResumeBuilderService;
use App\Services\ResumeContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ResumeController extends Controller
{
    public function __construct(
        private ResumeBuilderService $resumeBuilder,
        private ResumeContent $resumeContent,
    ) {}

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
            'resumeContent' => $this->resumeContent,
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

    /** Resume Editor: form on the left, live preview of the fixed template on the right. */
    public function edit(Resume $resume)
    {
        $this->authorize('update', $resume);

        if (! $this->resumeContent->isEditable($resume->content)) {
            return redirect()->route('student.resume.index')
                ->with('error', 'This resume uses an older format. Generate a new resume to edit it.');
        }

        $isNewest = $this->resumeBuilder->resumesFor($resume->student)->first()?->is($resume) ?? false;

        return view('student.resume.edit', [
            'resume' => $resume,
            'editor' => $this->resumeContent->toEditor($resume->content),
            'previewHtml' => $this->resumeBuilder->previewHtml($resume->content),
            'maxResumes' => ResumeBuilderService::MAX_GENERATED_RESUMES,
            'resumeCount' => $this->resumeBuilder->resumesFor($resume->student)->count(),
            'isNewest' => $isNewest,
        ]);
    }

    /** Live preview HTML for unsaved editor data. Nothing is stored. */
    public function preview(Request $request, Resume $resume): Response
    {
        $this->authorize('update', $resume);
        $content = $this->editedContent($request, $resume, forPreview: true);

        return response($this->resumeBuilder->previewHtml($content), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Save changes: this resume only. The profile is untouched unless the student opts in for the summary. */
    public function update(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);
        $content = $this->editedContent($request, $resume);

        $this->resumeBuilder->updateContent($resume, $content);
        $profileUpdated = $this->updateProfileSummary($request, $resume, $content);

        return response()->json([
            'message' => $profileUpdated ? 'Changes saved. Your profile summary was updated too.' : 'Changes saved.',
            'saved_at' => now('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    /** Save as new resume: keeps the original as is. The 3-resume limit still applies. */
    public function storeCopy(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);
        $content = $this->editedContent($request, $resume, generatedNow: true);

        $copy = $this->resumeBuilder->store($resume->student, $content);
        $profileUpdated = $this->updateProfileSummary($request, $resume, $content);

        return response()->json([
            'message' => 'Saved as a new resume.'.($profileUpdated ? ' Your profile summary was updated too.' : ''),
            'edit_url' => route('student.resume.edit', $copy),
        ], 201);
    }

    /** Download PDF straight from the editor, matching the preview even before saving. */
    public function pdf(Request $request, Resume $resume): Response
    {
        $this->authorize('update', $resume);
        $content = $this->editedContent($request, $resume);

        return response($this->resumeBuilder->pdfFor($content, $resume->student), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="resume-'.$resume->student_id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
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

    /** Validates the editor's data and turns it into stored resume content (422 JSON on errors). */
    private function editedContent(Request $request, Resume $resume, bool $forPreview = false, bool $generatedNow = false): array
    {
        abort_unless($this->resumeContent->isEditable($resume->content), 422, 'This resume uses an older format and cannot be edited.');

        $data = $this->resumeContent->clean($request->all());
        Validator::make($data, $this->resumeContent->rules($forPreview), $this->resumeContent->messages())->validate();

        $base = $resume->content;
        if ($generatedNow) {
            $base['generated_at'] = now('Asia/Manila')->toIso8601String();
        }

        return $this->resumeContent->fromEditor($data, $base);
    }

    private function updateProfileSummary(Request $request, Resume $resume, array $content): bool
    {
        if (! $request->boolean('update_profile_summary')) {
            return false;
        }

        $student = $resume->student;
        $student->update(['career_objectives' => $content['summary']]);
        $student->calculateProfileCompletion();

        return true;
    }
}
