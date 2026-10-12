<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\PortfolioFileAccess;

class PortfolioPolicy
{
    public function create(User $user): bool
    {
        return $user->isRole(UserRole::Student);
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return app(PortfolioFileAccess::class)->view($user, $portfolio);
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return app(PortfolioFileAccess::class)->update($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return app(PortfolioFileAccess::class)->delete($user, $portfolio);
    }

    public function download(User $user, Portfolio $portfolio): bool
    {
        return app(PortfolioFileAccess::class)->download($user, $portfolio);
    }

    public function preview(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
