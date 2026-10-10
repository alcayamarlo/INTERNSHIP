<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Internship;
use App\Models\InternshipApplication;
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

        return DB::transaction(function () use ($student, $internship, $applicant, $coverLetter, $matchPercentage) {
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
