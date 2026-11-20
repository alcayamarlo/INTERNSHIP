<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AccountDeletionRequest;
use App\Models\Placement;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Student accounts past the retention period (config privacy.retention_years, default 3 years): listed for an admin
 * to review. Nothing is ever deleted from here; an admin opens a deletion request, which still needs approval.
 *
 * "Last activity" is the later of the last sign-in (or, if the account never signed in, the day it was created) and
 * the end of the student's last OJT. A student with an OJT still running, or with a deletion request waiting, is not listed.
 */
class RetentionReview
{
    public function cutoff(): CarbonInterface
    {
        return now()->subYears((int) config('privacy.retention_years', 3));
    }

    /** @return Collection<int, object{student: Student, last_activity: CarbonInterface, basis: string}> oldest first */
    public function candidates(): Collection
    {
        $cutoff = $this->cutoff();

        return Student::query()
            ->with('user')
            ->withMax('placements', 'end_date')
            ->withMax('placements', 'completed_at')
            ->whereHas('user', fn ($q) => $q->where('role', UserRole::Student->value)->whereNull('anonymised_at'))
            ->whereDoesntHave('placements', fn ($q) => $q->whereIn('status', [Placement::STATUS_ACTIVE, Placement::STATUS_PENDING_VALIDATION]))
            ->whereDoesntHave('user.deletionRequests', fn ($q) => $q->where('status', AccountDeletionRequest::STATUS_PENDING))
            ->get()
            ->map(function (Student $student) {
                $signedIn = $student->user->last_login_at ?? $student->user->created_at;
                $ojt = collect([$student->placements_max_end_date, $student->placements_max_completed_at])->filter()->map(fn ($date) => \Illuminate\Support\Carbon::parse($date))->max();
                $useOjt = $ojt && $ojt->greaterThan($signedIn);

                return (object) ['student' => $student, 'last_activity' => $useOjt ? $ojt : $signedIn, 'basis' => $useOjt ? 'OJT ended' : ($student->user->last_login_at ? 'Last sign-in' : 'Never signed in, created')];
            })
            ->filter(fn (object $row) => $row->last_activity->lessThan($cutoff))
            ->sortBy(fn (object $row) => $row->last_activity->getTimestamp())
            ->values();
    }
}
