<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->isRole(UserRole::Student)
            && $user->student?->id === $certificate->student_id;
    }
}
