<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Mail\InterviewInvitationMail;
use App\Models\InternshipApplication;
use App\Services\ActivityLogService;
use App\Services\CompanyVisibleProfile;
use App\Services\MessagingEligibility;
use App\Services\NotificationService;
use App\Services\PlacementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;
use Illuminate\Validation\ValidationException;

class ApplicantController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private PlacementService $placementService,
        private CompanyVisibleProfile $companyProfile,
        private MessagingEligibility $messaging,
    ) {}

    public function index()
    {
        $applications = InternshipApplication::whereHas(
            'internship',
            fn ($q) => $q->where('employer_id', Auth::user()->employer->id)
        )
            ->with(['student.user', 'internship'])
            ->latest('applied_at')
            ->paginate(15);
        $applications->getCollection()->each(fn (InternshipApplication $application) => $application
            ->setAttribute('company_profile', $this->companyProfile->summary($application->student)));

        return view('employer.applicants.index', compact('applications'));
    }

    public function show(InternshipApplication $application)
    {
        $this->authorize('view', $application);

        $application->load(['student.user', 'internship', 'placement']);
        $applicationResume = $application->student->resumes()->latest('generated_at')->latest('id')->first();
        if ($applicationResume) {
            $applicationResume->setAttribute('file_size_display', $this->fileSize($applicationResume->file_path));
        }

        $profile = $this->companyProfile->for($application->student, Auth::user(), $application);
        $availableStatuses = $application->status->allowedNextStatuses();

        return view('employer.applicants.show', compact('application', 'applicationResume', 'availableStatuses', 'profile'));
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, InternshipApplication $application)
    {
        $this->authorize('updateStatus', $application);

        $validated = $request->validated();
        $status = $validated['status'] instanceof ApplicationStatus
            ? $validated['status']
            : ApplicationStatus::from($validated['status']);

        $studentUser = $application->student->user;
        $chatWasOpen = $this->messaging->companyChatOpen(Auth::user(), $studentUser);

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

            if ($status === ApplicationStatus::Accepted) {
                $this->placementService->openForApplication($application);
            }

            $this->activityLog->log(Auth::user(), 'application_status_update', [
                'application_id' => $application->id,
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
            ]);

            return $application;
        });

        $invitationSent = true;
        // One notification per change; it also says when the company chat opens or closes.
        $chatOpen = $this->messaging->companyChatOpen(Auth::user(), $studentUser);
        $title = $application->internship->title;
        [$notificationTitle, $notificationMessage, $notificationData] = match (true) {
            ! $chatWasOpen && $chatOpen => [
                $status === ApplicationStatus::Reviewed ? 'Application reviewed' : 'Application Status Updated',
                $status === ApplicationStatus::Reviewed
                    ? "Your application for {$title} was reviewed. You can now message the company."
                    : "Your application for {$title} is now {$status->label()}. You can now message the company.",
                ['application_id' => $application->id, 'messaging_open_with' => Auth::id()],
            ],
            $chatWasOpen && ! $chatOpen => [
                'Application Status Updated',
                "Your application for {$title} is now {$status->label()}. Messaging with {$application->internship->employer->company_name} is now closed; you can still read past messages.",
                ['application_id' => $application->id],
            ],
            default => [
                'Application Status Updated',
                "Your application for {$title} is now {$status->label()}",
                ['application_id' => $application->id],
            ],
        };
        $this->notificationService->send($studentUser, 'application_update', $notificationTitle, $notificationMessage, $notificationData);

        if ($status === ApplicationStatus::Interview && $application->interview_at) {
            // The status change is already saved; a mail failure only warns the employer.
            $invitationSent = app(\App\Services\MailSender::class)->send($studentUser->email, new InterviewInvitationMail(
                internshipTitle: $application->internship->title,
                interviewAt: $application->interview_at,
                companyName: $application->internship->employer?->company_name ?? 'The employer',
                location: $application->internship->location ?? '',
                recipientName: $studentUser->name,
            ), 'interview_invitation');
        }

        $statusMessage = $invitationSent
            ? 'Application status updated.'
            : "Application status updated, but we couldn't send the interview email. Please try again later or contact the student directly.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'status_badge_class' => $application->status->badgeClass(),
                'available_statuses' => array_map(
                    fn (ApplicationStatus $availableStatus) => [
                        'value' => $availableStatus->value,
                        'label' => $availableStatus->label(),
                    ],
                    $application->status->allowedNextStatuses()
                ),
                'redirect_url' => route('employer.applicants.index'),
            ]);
        }

        return redirect()->route('employer.applicants.index')
            ->with($invitationSent ? 'success' : 'error', $statusMessage);
    }

    public function downloadResume(InternshipApplication $application)
    {
        $this->authorize('downloadResume', $application);

        $resume = $application->student->resumes()->latest('generated_at')->latest('id')->first();

        abort_unless($resume && $resume->file_path, 404, 'Resume not found.');

        $this->authorize('download', $resume);

        return app(\App\Services\ResumeBuilderService::class)->pdfResponse($resume, Auth::user(), download: true);
    }

    private function fileSize(?string $path): string
    {
        if (! $path) {
            return 'Unavailable';
        }

        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return number_format(Storage::disk($disk)->size($path) / 1024, 1).' KB';
            }
        }

        return 'Unavailable';
    }
}
