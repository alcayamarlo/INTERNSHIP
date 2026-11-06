<?php

namespace App\Observers;

use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\LiveUpdates;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns saves of the records shown in live lists into LiveUpdate pings
 * (registered in AppServiceProvider). Topics:
 *   applications  InternshipApplication
 *   placements    Placement, AttendanceLog, JournalEntry, Evaluation
 */
class LiveUpdateObserver
{
    public function __construct(private LiveUpdates $live) {}

    public function saved(Model $model): void
    {
        $this->changed($model);
    }

    public function deleted(Model $model): void
    {
        $this->changed($model);
    }

    public function restored(Model $model): void
    {
        $this->changed($model);
    }

    private function changed(Model $model): void
    {
        if (! $this->live->enabled()) {
            return;
        }

        match (true) {
            $model instanceof InternshipApplication => $this->live->forStudentRecord(
                $model->student_id,
                Internship::withTrashed()->whereKey($model->internship_id)->value('employer_id'),
                'applications'
            ),
            $model instanceof Placement => $this->live->forStudentRecord($model->student_id, $model->employer_id, 'placements'),
            $model instanceof AttendanceLog, $model instanceof JournalEntry, $model instanceof Evaluation => $this->forPlacement($model->placement_id),
            default => null,
        };
    }

    private function forPlacement(?int $placementId): void
    {
        $placement = $placementId ? Placement::withTrashed()->whereKey($placementId)->first(['id', 'student_id', 'employer_id']) : null;
        if ($placement) {
            $this->live->forStudentRecord($placement->student_id, $placement->employer_id, 'placements');
        }
    }
}
