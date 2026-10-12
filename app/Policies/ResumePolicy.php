<?php

namespace App\Policies;

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
}
