<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\PortfolioCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Mail\InterviewInvitationMail;
use App\Models\InternshipApplication;
use App\Services\ActivityLogService;
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
        private PlacementService $placementService
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
            'student.portfolios.reviewerUser:id,name',
            'student.certificates.reviewerUser:id,name',
            'student.resumes.reviewerUser:id,name',
            'internship',
        ]);
        $applicationResumeQuery = $application->student->resumes();
        $applicationResume = $application->status->isActive()
            ? $applicationResumeQuery->withArchived()->latest()->first()
            : $applicationResumeQuery->latest()->first();
        if ($applicationResume) {
            $applicationResume->setAttribute('file_size_display', $this->fileSize($applicationResume->file_path));
        }

        $student = $application->student;
        if ($application->status === ApplicationStatus::Withdrawn) {
            $student->setRelation('portfolios', collect());
            $student->setRelation('certificates', collect());
            $student->setRelation('resumes', collect());
        }
        $student->portfolios->each(fn ($file) => $file->setAttribute('file_size_display', $this->fileSize($file->file_path)));
        $student->certificates->each(fn ($file) => $file->setAttribute('file_size_display', $this->fileSize($file->file_path)));
        $student->resumes->each(fn ($file) => $file->setAttribute('file_size_display', $this->fileSize($file->file_path)));
        $verifiedCertificates = $student->certificates
            ->filter(fn ($file) => $file->verification_status === 'verified')
            ->values();
        $certificateKeys = $verifiedCertificates
            ->map(fn ($file) => $file->title.'|'.$file->file_path)
            ->flip();
        $portfolioCertificates = $student->portfolios
            ->filter(fn ($file) => $file->category === PortfolioCategory::Certificate
                && $file->verification_status === 'verified'
                && ! $certificateKeys->has($file->title.'|'.$file->file_path));
        $student->setRelation(
            'portfolios',
            $student->portfolios
                ->filter(fn ($file) => $file->category === PortfolioCategory::Project)
                ->values()
        );
        $student->setRelation('certificates', $verifiedCertificates->concat($portfolioCertificates)->values());

        $availableStatuses = $application->status->allowedNextStatuses();

        return view('employer.applicants.show', compact('application', 'applicationResume', 'availableStatuses'));
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

        $studentUser = $application->student->user;
        $invitationSent = true;
        $this->notificationService->send(
            $studentUser,
            'application_update',
            'Application Status Updated',
            'Your application for '.$application->internship->title.' is now '.$application->status->label(),
            ['application_id' => $application->id]
        );

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
            ]);
        }

        return back()->with($invitationSent ? 'success' : 'error', $statusMessage);
    }

    public function downloadResume(InternshipApplication $application)
    {
        $this->authorize('downloadResume', $application);

        $resume = $application->student->resumes()->latest()->first();

        abort_unless($resume && $resume->file_path, 404, 'Resume not found.');

        $this->authorize('download', $resume);
        $disk = app(\App\Services\ResumeBuilderService::class)->diskFor($resume);
        abort_unless($disk, 404, 'Resume file not found.');

        return Storage::disk($disk)->download($resume->file_path, 'resume-'.$resume->student_id.'.pdf', ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']);
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
