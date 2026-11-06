<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlacementService
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
    ) {}

    /**
     * Opens the OJT record for an accepted application. Safe to call more than once for the
     * same application. Call it inside a DB transaction (ApplicantController does), so a
     * refusal also rolls back the "accepted" status change.
     *
     * @throws ValidationException when the student already has an open OJT from another application
     */
    public function openForApplication(InternshipApplication $application): Placement
    {
        $application->loadMissing('internship', 'student.academicProgram', 'student.user');

        // Lock the student row so two companies accepting the same student at the same moment
        // are handled one after the other; the second one then sees the first placement.
        Student::query()->whereKey($application->student_id)->lockForUpdate()->first();

        $existing = $this->openPlacementFor($application->student_id, exceptApplicationId: $application->id);
        if ($existing) {
            throw $this->alreadyPlaced($application);
        }

        try {
            return $this->firstOrCreatePlacement($application);
        } catch (UniqueConstraintViolationException $exception) {
            // Database backstop (placements_one_open_per_student) for anything the check above missed.
            throw $this->alreadyPlaced($application);
        }
    }

    /** The student's open (pending validation or active) placement, if any. */
    public function openPlacementFor(int $studentId, ?int $exceptApplicationId = null): ?Placement
    {
        return Placement::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [Placement::STATUS_PENDING_VALIDATION, Placement::STATUS_ACTIVE])
            ->when($exceptApplicationId, fn ($query) => $query->where('application_id', '<>', $exceptApplicationId))
            ->first();
    }

    private function alreadyPlaced(InternshipApplication $application): ValidationException
    {
        $name = $application->student?->user?->name ?? 'This student';

        // The other company is not named: employers should not learn where else a student applied.
        return ValidationException::withMessages([
            'status' => "{$name} already has an ongoing OJT placement with another company, so this application cannot be accepted. A student can only have one OJT at a time. Contact the student's coordinator if this needs to change.",
        ]);
    }

    private function firstOrCreatePlacement(InternshipApplication $application): Placement
    {
        return Placement::withTrashed()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'student_id' => $application->student_id,
                'employer_id' => $application->internship->employer_id,
                'internship_id' => $application->internship_id,
                'start_date' => now()->toDateString(),
                // The program's criteria for this term win, then the program, then the academic setting.
                'required_hours' => ($application->student ? app(EligibilityCriteria::class)->forStudent($application->student)['required_hours'] : null)
                    ?: $application->student?->academicProgram?->required_internship_hours
                    ?: (AcademicSetting::current()->required_ojt_hours ?: 300),
                // The coordinator validates it (company, MOA, program match) before hours can be logged.
                'status' => Placement::STATUS_PENDING_VALIDATION,
            ]
        );
    }

    /**
     * Completes the placement once approved hours meet the target and the
     * supervisor evaluation is in. Returns true when it changed state.
     */
    public function completeIfEligible(Placement $placement, ?User $actor = null): bool
    {
        $placement->refresh();

        if (! $placement->isActive() || ! $placement->hasMetRequiredHours() || ! $placement->evaluation()->exists()) {
            return false;
        }

        DB::transaction(function () use ($placement, $actor): void {
            $placement->update([
                'status' => Placement::STATUS_COMPLETED,
                'completed_at' => now(),
                'end_date' => $placement->end_date ?? now()->toDateString(),
            ]);

            $application = InternshipApplication::query()->lockForUpdate()->find($placement->application_id);
            if ($application && $application->status === ApplicationStatus::Accepted) {
                $application->update(['status' => ApplicationStatus::Completed]);
                $application->statusHistory()->create([
                    'actor_id' => $actor?->id,
                    'status' => ApplicationStatus::Completed->value,
                    'notes' => 'Required OJT hours rendered and supervisor evaluation submitted.',
                ]);
            }

            $this->activityLog->log($actor, 'placement_completed', ['placement_id' => $placement->id]);
        });

        $placement->loadMissing('student.user', 'internship');
        if ($studentUser = $placement->student?->user) {
            $this->notificationService->send(
                $studentUser,
                'ojt_update',
                'OJT Completed',
                'You have completed your OJT for '.$placement->internship->title.'. Your coordinator will review it for clearance.',
                ['placement_id' => $placement->id]
            );
        }

        $this->notifyCoordinators($placement);

        return true;
    }

    public function clear(Placement $placement, User $coordinator, ?string $remarks): void
    {
        $placement->update([
            'cleared_at' => now(),
            'cleared_by_user_id' => $coordinator->id,
            'clearance_remarks' => $remarks,
            'certificate_number' => $placement->certificate_number ?? $this->certificateNumber($placement),
        ]);

        $this->activityLog->log($coordinator, 'placement_cleared', ['placement_id' => $placement->id]);

        $placement->loadMissing('student.user');
        if ($studentUser = $placement->student?->user) {
            $this->notificationService->send(
                $studentUser,
                'ojt_update',
                'OJT Cleared',
                'Your coordinator cleared your OJT. Your certificate of completion is ready to download.',
                ['placement_id' => $placement->id]
            );
        }
    }

    private function notifyCoordinators(Placement $placement): void
    {
        $student = $placement->student;
        if (! $student?->institution_id) {
            return;
        }

        Coordinator::query()
            ->where('institution_id', $student->institution_id)
            ->with('user')
            ->get()
            ->filter(fn (Coordinator $coordinator) => $coordinator->user && $coordinator->canAccessStudent($student))
            ->each(fn (Coordinator $coordinator) => $this->notificationService->send(
                $coordinator->user,
                'ojt_update',
                'OJT ready for clearance',
                ($student->user?->name ?? 'A student').' completed the OJT for '.$placement->internship->title.' and is ready for clearance.',
                ['placement_id' => $placement->id]
            ));
    }

    private function certificateNumber(Placement $placement): string
    {
        return 'OJT-'.now()->format('Y').'-'.str_pad((string) $placement->id, 5, '0', STR_PAD_LEFT);
    }
}
