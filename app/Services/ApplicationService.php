<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        private CompetencyMatchingService $matchingService,
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private InternshipEligibility $eligibility
    ) {}

    /**
     * Submit a student application for an internship.
     *
     * @throws ValidationException
     */
    public function apply(Student $student, Internship $internship, Resume $resume, User $applicant, ?string $coverLetter = null): InternshipApplication
    {
        if ((int) $resume->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'resume_id' => 'Choose one of your resumes.',
            ]);
        }

        $this->ensureCanApply($student, $internship);

        $matchPercentage = $this->matchingService->calculateMatch($student, $internship);

        return DB::transaction(function () use ($student, $internship, $resume, $applicant, $coverLetter, $matchPercentage) {
            try {
                $application = InternshipApplication::create([
                    'internship_id' => $internship->id,
                    'student_id' => $student->id,
                    'resume_id' => $resume->id,
                    'status' => ApplicationStatus::Applied,
                    'cover_letter' => $coverLetter,
                    'match_percentage' => $matchPercentage,
                    'applied_at' => now(),
                ]);
            } catch (QueryException) {
                throw ValidationException::withMessages([
                    'internship' => 'You have already applied for this internship.',
                ]);
            }

            $this->activityLog->log($applicant, 'application_submit', [
                'application_id' => $application->id,
                'internship_id' => $internship->id,
            ]);

            DB::afterCommit(function () use ($internship, $applicant, $application) {
                $this->notificationService->send(
                    $internship->employer->user,
                    'application',
                    'New Application Received',
                    $applicant->name.' applied for '.$internship->title,
                    ['application_id' => $application->id]
                );
            });

            return $application;
        });
    }

    /**
     * The application behind an accepted invitation. It starts as Reviewed because the company
     * already showed interest, with the same status history entry a Reviewed update writes.
     * Call inside the transaction that marks the invitation accepted.
     *
     * @throws ValidationException
     */
    public function applyFromInvitation(InternshipInvitation $invitation, Resume $resume, User $applicant): InternshipApplication
    {
        $student = $invitation->student;
        $internship = $invitation->internship;
        $this->ensureCanApply($student, $internship);

        try {
            $application = InternshipApplication::create([
                'internship_id' => $internship->id,
                'student_id' => $student->id,
                'resume_id' => $resume->id,
                'status' => ApplicationStatus::Reviewed,
                'match_percentage' => $this->matchingService->calculateMatch($student, $internship),
                'applied_at' => now(),
            ]);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'internship' => 'You have already applied for this internship.',
            ]);
        }

        $application->statusHistory()->create([
            'actor_id' => $applicant->id,
            'status' => ApplicationStatus::Reviewed->value,
            'notes' => 'Started from the company\'s invitation.',
        ]);

        $this->activityLog->log($applicant, 'application_submit', [
            'application_id' => $application->id,
            'internship_id' => $internship->id,
            'invitation_id' => $invitation->id,
        ]);

        return $application;
    }

    /**
     * School rules and internship rules shared by Apply and accepting an invitation.
     *
     * @throws ValidationException
     */
    public function ensureCanApply(Student $student, Internship $internship): void
    {
        $eligibilityReasons = $this->eligibility->reasons($student);
        if ($eligibilityReasons !== []) {
            throw ValidationException::withMessages(['eligibility' => $eligibilityReasons]);
        }

        if (! $internship->isAcceptingApplications()) {
            throw ValidationException::withMessages([
                'internship' => $internship->expires_at && $internship->expires_at->isPast()
                    ? 'This internship posting has expired and is no longer accepting applications.'
                    : 'This internship is no longer accepting applications.',
            ]);
        }

        if (InternshipApplication::withTrashed()->where('internship_id', $internship->id)
            ->where('student_id', $student->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'internship' => 'You have already applied for this internship.',
            ]);
        }

        if ($internship->requires_character_reference && ! $student->characterReferences()->exists()) {
            throw ValidationException::withMessages([
                'character_reference' => 'This internship requires at least one character reference. Add one on your profile under Character Reference, then apply.',
            ]);
        }
    }
}
