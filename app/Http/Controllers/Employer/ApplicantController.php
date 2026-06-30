<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Models\InternshipApplication;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicantController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
    ) {}

    public function index()
    {
        $applications = InternshipApplication::whereHas(
            'internship',
            fn ($q) => $q->where('employer_id', Auth::user()->employer->id)
        )
            ->with(['student.user', 'student.competencies', 'internship'])
            ->latest('applied_at')
            ->paginate(15);

        return view('employer.applicants.index', compact('applications'));
    }

    public function show(InternshipApplication $application)
    {
        $this->authorize('view', $application);

        $application->load([
            'student.user',
            'student.competencies',
            'student.portfolios',
            'student.certificates',
            'student.resumes',
            'internship',
        ]);

        return view('employer.applicants.show', compact('application'));
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, InternshipApplication $application)
    {
        $this->authorize('updateStatus', $application);

        $application->update($request->validated());

        $studentUser = $application->student->user;
        $this->notificationService->send(
            $studentUser,
            'application_update',
            'Application Status Updated',
            'Your application for '.$application->internship->title.' is now '.$application->status->label(),
            ['application_id' => $application->id]
        );

        $this->activityLog->log(Auth::user(), 'application_status_update', [
            'application_id' => $application->id,
            'status' => $application->status->value,
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function downloadResume(InternshipApplication $application)
    {
        $this->authorize('downloadResume', $application);

        $resume = $application->student->resumes()->latest()->first();

        abort_unless($resume && $resume->file_path, 404, 'Resume not found.');

        if (! Storage::disk('public')->exists($resume->file_path)) {
            abort(404, 'Resume file not found.');
        }

        return Storage::disk('public')->download($resume->file_path);
    }
}
