<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Models\User;
use App\Services\PortfolioFileAccess;

class ResumePolicy
{
    public function view(User $user, Resume $resume): bool
    {
        return app(PortfolioFileAccess::class)->view($user, $resume);
    }

    public function download(User $user, Resume $resume): bool
    {
        return app(PortfolioFileAccess::class)->download($user, $resume);
    }

    public function archive(User $user, Resume $resume): bool
    {
        return $user->isRole(UserRole::Student)
            && (int) $user->student?->id === (int) $resume->student_id;
    }

    public function restore(User $user, Resume $resume): bool
    {
        return $user->isRole(UserRole::Student)
            && (int) $user->student?->id === (int) $resume->student_id;
    }

    public function viewForApplication(User $user, Resume $resume, InternshipApplication $application): bool
    {
        return app(PortfolioFileAccess::class)->viewForApplication($user, $resume, $application);
    }

    public function downloadForApplication(User $user, Resume $resume, InternshipApplication $application): bool
    {
        return app(PortfolioFileAccess::class)->viewForApplication($user, $resume, $application);
    }
}
