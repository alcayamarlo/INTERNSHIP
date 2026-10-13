<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Internship;
use App\Models\InternshipApplication;
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

        if ($resume->archived_at || (int) $resume->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'resume_id' => 'Choose one of your non-archived resumes.',
            ]);
        }

        if (InternshipApplication::withTrashed()->where('internship_id', $internship->id)
            ->where('student_id', $student->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'internship' => 'You have already applied for this internship.',
            ]);
        }

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
}
