<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->isRole(UserRole::Student)
            && $user->student?->id === $portfolio->student_id;
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function download(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function preview(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
