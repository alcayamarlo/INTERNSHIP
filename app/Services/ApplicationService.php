<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        private CompetencyMatchingService $matchingService,
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
    ) {}

    /**
     * Submit a student application for an internship.
     *
     * @throws ValidationException
     */
    public function apply(Student $student, Internship $internship, User $applicant, ?string $coverLetter = null): InternshipApplication
    {
        if ($internship->status !== 'open') {
            throw ValidationException::withMessages([
                'internship' => 'This internship is no longer accepting applications.',
            ]);
        }

        if (InternshipApplication::where('internship_id', $internship->id)
            ->where('student_id', $student->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'internship' => 'You have already applied for this internship.',
            ]);
        }

        $matchPercentage = $this->matchingService->calculateMatch($student, $internship);

        try {
            $application = InternshipApplication::create([
                'internship_id' => $internship->id,
                'student_id' => $student->id,
                'status' => ApplicationStatus::Submitted,
                'cover_letter' => $coverLetter,
                'match_percentage' => $matchPercentage,
                'applied_at' => now(),
            ]);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'internship' => 'You have already applied for this internship.',
            ]);
        }

        $employerUser = $internship->employer->user;
        $this->notificationService->send(
            $employerUser,
            'application',
            'New Application Received',
            $applicant->name.' applied for '.$internship->title,
            ['application_id' => $application->id]
        );

        $this->activityLog->log($applicant, 'application_submit', [
            'application_id' => $application->id,
            'internship_id' => $internship->id,
        ]);

        return $application;
    }
}
