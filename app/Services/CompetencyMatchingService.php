<?php

namespace App\Services;

use App\Enums\ProficiencyLevel;
use App\Models\Internship;
use App\Models\Student;

class CompetencyMatchingService
{
    public function calculateMatch(Student $student, Internship $internship): int
    {
        $requirements = $internship->requirementsList()->get();

        if ($requirements->isEmpty()) {
            return 0;
        }

        $studentCompetencies = $student->competencies()->get();
        $matched = 0;

        foreach ($requirements as $requirement) {
            $requiredScore = $requirement->required_level instanceof ProficiencyLevel
                ? $requirement->required_level->score()
                : ProficiencyLevel::from($requirement->required_level)->score();

            $studentMatch = $studentCompetencies->first(function ($competency) use ($requirement) {
                $name = strtolower($requirement->requirement_name);

                return str_contains(strtolower($competency->name), $name)
                    || ($requirement->competency_id && $competency->competency_id === $requirement->competency_id);
            });

            if ($studentMatch) {
                $studentScore = $studentMatch->proficiency_level->score();
                if ($studentScore >= $requiredScore) {
                    $matched++;
                } elseif ($studentScore >= ($requiredScore * 0.6)) {
                    $matched += 0.5;
                }
            }
        }

        return (int) round(($matched / $requirements->count()) * 100);
    }

    public function getRecommendations(Student $student, int $limit = 10)
    {
        $internships = Internship::query()
            ->open()
            ->with(['employer', 'requirementsList'])
            ->latest()
            ->get();

        $recommendations = $internships->map(function (Internship $internship) use ($student) {
            $internship->match_percentage = $this->calculateMatch($student, $internship);
            return $internship;
        })
            ->sortByDesc('match_percentage')
            ->take($limit)
            ->values();

        return $recommendations;
    }
}
