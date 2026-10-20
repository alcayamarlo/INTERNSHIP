<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlacementService
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
    ) {}

    /**
     * Opens the OJT record for an accepted application. Safe to call more than once.
     */
    public function openForApplication(InternshipApplication $application): Placement
    {
        $application->loadMissing('internship');

        return Placement::withTrashed()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'student_id' => $application->student_id,
                'employer_id' => $application->internship->employer_id,
                'internship_id' => $application->internship_id,
                'start_date' => now()->toDateString(),
                'required_hours' => AcademicSetting::current()->required_ojt_hours ?: 300,
                'status' => Placement::STATUS_ACTIVE,
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
