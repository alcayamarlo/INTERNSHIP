<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Competency;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * @return array<string, Collection>
     */
    public function search(User $user, string $query): array
    {
        $results = [
            'students' => collect(),
            'companies' => collect(),
            'skills' => collect(),
            'competencies' => collect(),
            'internships' => collect(),
        ];

        if (strlen($query) < 2) {
            return $results;
        }

        if ($this->canSearchStudents($user)) {
            $studentQuery = Student::with('user')
                ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"));

            if ($user->isRole(UserRole::Coordinator) && $user->coordinator) {
                $studentQuery->where('institution_id', $user->coordinator->institution_id);
            }

            $results['students'] = $studentQuery->take(5)->get();
        }

        if ($this->canSearchCompanies($user)) {
            $results['companies'] = Employer::where('company_name', 'like', "%{$query}%")->take(5)->get();
        }

        if ($this->canSearchSkills($user)) {
            $results['skills'] = Skill::where('name', 'like', "%{$query}%")->take(5)->get();
            $results['competencies'] = Competency::where('name', 'like', "%{$query}%")->take(5)->get();
        }

        if ($this->canSearchInternships($user)) {
            $results['internships'] = Internship::open()
                ->where('title', 'like', "%{$query}%")
                ->with('employer')
                ->take(5)
                ->get();
        }

        return $results;
    }

    private function canSearchStudents(User $user): bool
    {
        return $user->isRole(UserRole::Admin, UserRole::Employer, UserRole::Coordinator);
    }

    private function canSearchCompanies(User $user): bool
    {
        return $user->isRole(UserRole::Admin, UserRole::Student, UserRole::Coordinator);
    }

    private function canSearchSkills(User $user): bool
    {
        return true;
    }

    private function canSearchInternships(User $user): bool
    {
        return $user->isRole(UserRole::Admin, UserRole::Student, UserRole::Coordinator);
    }
}
