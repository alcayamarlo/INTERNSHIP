<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\PortfolioCategory;
use App\Enums\UserRole;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortfolioFileAccess
{
    public function view(User $user, Model $file): bool
    {
        $category = $this->category($file);
        $studentId = (int) $file->student_id;

        if ($user->isRole(UserRole::Admin)) {
            return true;
        }

        if ($user->isRole(UserRole::Student)) {
            return (int) $user->student?->id === $studentId;
        }

        if ($user->isRole(UserRole::Coordinator)) {
            $student = Student::find($studentId);

            return $student !== null && $user->coordinator?->canAccessStudent($student) === true;
        }

        if ($user->isRole(UserRole::Employer)) {
            if ($category === PortfolioCategory::DeploymentDocument) {
                return false;
            }

            if ($category === PortfolioCategory::Certificate && $file->verification_status !== 'verified') {
                return false;
            }

            return $this->hasEmployerApplication($user, $studentId);
        }

        return false;
    }

    public function download(User $user, Model $file): bool
    {
        if (! $this->view($user, $file)) {
            return false;
        }

        if ($user->isRole(UserRole::Employer)) {
            return $file instanceof \App\Models\Resume
                && $this->category($file) === PortfolioCategory::Resume;
        }

        return true;
    }

    public function update(User $user, Model $file): bool
    {
        if ($user->isRole(UserRole::Admin)) {
            return true;
        }

        if (! $user->isRole(UserRole::Student)
            || (int) $user->student?->id !== (int) $file->student_id) {
            return false;
        }

        if ($this->category($file) === PortfolioCategory::DeploymentDocument) {
            return $file->verification_status !== 'approved'
                && (int) ($file->uploaded_by_user_id ?? $user->id) === (int) $user->id;
        }

        return true;
    }

    public function delete(User $user, Model $file): bool
    {
        if ($user->isRole(UserRole::Admin)) {
            return true;
        }

        if (! $user->isRole(UserRole::Student)
            || (int) $user->student?->id !== (int) $file->student_id) {
            return false;
        }

        $category = $this->category($file);
        if ($category === PortfolioCategory::DeploymentDocument
            && ($file->verification_status === 'approved'
                || (int) ($file->uploaded_by_user_id ?? $user->id) !== (int) $user->id)) {
            return false;
        }

        return ! ($category === PortfolioCategory::Certificate && $file->verification_status === 'verified');
    }

    private function category(Model $file): PortfolioCategory
    {
        $category = $file->getAttribute('category');

        return $category instanceof PortfolioCategory
            ? $category
            : PortfolioCategory::tryFrom((string) $category) ?? PortfolioCategory::Project;
    }

    private function hasEmployerApplication(User $user, int $studentId): bool
    {
        $employerId = $user->employer?->id;
        if (! $employerId) {
            return false;
        }

        return InternshipApplication::query()
            ->where('student_id', $studentId)
            ->where('status', '<>', ApplicationStatus::Withdrawn->value)
            ->whereHas('internship', fn ($query) => $query->where('employer_id', $employerId))
            ->exists();
    }
}
