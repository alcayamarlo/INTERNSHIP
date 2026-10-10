<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\DashboardCacheInvalidator;
use Illuminate\Database\Eloquent\Model;

class DashboardCacheObserver
{
    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $invalidator = app(DashboardCacheInvalidator::class);

        match (true) {
            $model instanceof User => $invalidator->userChanged($model),
            $model instanceof Student => $invalidator->studentChanged($model),
            $model instanceof InternshipApplication => $invalidator->applicationChanged($model),
            $model instanceof Internship => $invalidator->internshipChanged($model),
            $model instanceof StudentCompetency,
            $model instanceof Certificate,
            $model instanceof Portfolio => $invalidator->evidenceChanged((int) $model->student_id),
            default => null,
        };
    }
}
