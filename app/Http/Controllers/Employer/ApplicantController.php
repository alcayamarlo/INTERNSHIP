<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Mail\InterviewInvitationMail;
use App\Models\InternshipApplication;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        return view('coordinator.employer.applicants.index', compact('applications'));
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

        $availableStatuses = $application->status->allowedNextStatuses();

        return view('coordinator.employer.applicants.show', compact('application', 'availableStatuses'));
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, InternshipApplication $application)
    {
        $this->authorize('updateStatus', $application);

        $validated = $request->validated();
        $status = $validated['status'] instanceof ApplicationStatus
            ? $validated['status']
            : ApplicationStatus::from($validated['status']);

        $application = DB::transaction(function () use ($application, $validated, $status) {
            $application = InternshipApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);
            $previousStatus = $application->status;

            if (! $previousStatus->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => 'That status transition is not allowed from the current application status.',
                ]);
            }

            $application->update([
                'status' => $status,
                'interview_at' => $validated['interview_at'] ?? $application->interview_at,
                'employer_notes' => $validated['employer_notes'] ?? $application->employer_notes,
            ]);

            $application->statusHistory()->create([
                'actor_id' => Auth::id(),
                'status' => $status->value,
                'notes' => $validated['employer_notes'] ?? null,
                'interview_at' => $validated['interview_at'] ?? null,
            ]);

            $this->activityLog->log(Auth::user(), 'application_status_update', [
                'application_id' => $application->id,
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
            ]);

            return $application;
        });

        $studentUser = $application->student->user;
        $this->notificationService->send(
            $studentUser,
            'application_update',
            'Application Status Updated',
            'Your application for '.$application->internship->title.' is now '.$application->status->label(),
            ['application_id' => $application->id]
        );

        if ($status === ApplicationStatus::Interview && $application->interview_at) {
            Mail::to($studentUser->email)->send(new InterviewInvitationMail(
                internshipTitle: $application->internship->title,
                interviewAt: $application->interview_at,
                companyName: $application->internship->employer?->company_name ?? 'The employer',
                location: $application->internship->location ?? '',
                recipientName: $studentUser->name,
            ));
        }

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
