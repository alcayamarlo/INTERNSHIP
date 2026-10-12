<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;
use App\Services\PortfolioFileAccess;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        return app(PortfolioFileAccess::class)->view($user, $certificate);
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return app(PortfolioFileAccess::class)->download($user, $certificate);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return app(PortfolioFileAccess::class)->update($user, $certificate);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return app(PortfolioFileAccess::class)->delete($user, $certificate);
    }
}
