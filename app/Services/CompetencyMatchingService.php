<?php

namespace App\Services;

use App\Enums\ProficiencyLevel;
use App\Models\Internship;
use App\Models\RecommendationFeedback;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CompetencyMatchingService
{
    public function calculateMatch(Student $student, Internship $internship): int
    {
        $cacheKey = "match_{$student->id}_{$internship->id}";

        return Cache::remember($cacheKey, 3600, function () use ($student, $internship) {
            return $this->buildRecommendationResult($student, $internship)['match_percentage'];
        });
    }

    public function getRecommendations(Student $student, int $limit = 10)
    {
        $cacheKey = "recommendations_{$student->id}_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($student, $limit) {
            $internships = Internship::query()
                ->open()
                ->with(['employer', 'requirementsList'])
                ->latest()
                ->get();

            $recommendations = $internships->map(function (Internship $internship) use ($student) {
                $result = $this->buildRecommendationResult($student, $internship);

                $internship->match_percentage = $result['match_percentage'];
                $internship->recommendation_details = $result['details'];

                return $internship;
            })
                ->sortByDesc('match_percentage')
                ->take($limit)
                ->values();

            return $recommendations;
        });
    }

    public function invalidateCache(Student $student): void
    {
        Cache::forget("recommendations_{$student->id}_10");
    }

    protected function buildRecommendationResult(Student $student, Internship $internship): array
    {
        $requirements = $internship->requirementsList()->get();

        if ($requirements->isEmpty()) {
            return [
                'match_percentage' => 0,
                'details' => [
                    'matched_requirements' => [],
                    'missing_requirements' => [],
                    'verified_match_count' => 0,
                    'total_requirements' => 0,
                    'explanation' => 'This internship has no listed requirements, so no skill matching could be calculated.',
                ],
            ];
        }

        $studentCompetencies = $student->competencies()->get();
        $feedback = $this->getStudentFeedback($student);
        $matchedRequirements = [];
        $missingRequirements = [];
        $verifiedMatchCount = 0;
        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($requirements as $requirement) {
            $requiredScore = $requirement->required_level instanceof ProficiencyLevel
                ? $requirement->required_level->score()
                : ProficiencyLevel::from($requirement->required_level)->score();

            $weight = $requirement->is_required ? 2 : 1;
            $totalWeight += $weight;

            $studentMatch = $studentCompetencies->first(function ($competency) use ($requirement) {
                $name = strtolower($requirement->requirement_name);

                return str_contains(strtolower($competency->name), $name)
                    || ($requirement->competency_id && $competency->competency_id === $requirement->competency_id);
            });

            if (! $studentMatch) {
                $missingRequirements[] = $requirement->requirement_name;
                continue;
            }

            $studentScore = $studentMatch->proficiency_level instanceof ProficiencyLevel
                ? $studentMatch->proficiency_level->score()
                : ProficiencyLevel::from($studentMatch->proficiency_level)->score();

            $isVerified = strtolower((string) ($studentMatch->verification_status ?? '')) === 'verified';

            if ($isVerified && $studentScore >= $requiredScore) {
                $matchedRequirements[] = $requirement->requirement_name;
                $verifiedMatchCount++;
                $weightedScore += $weight * min($studentScore / $requiredScore, 1.0);
                continue;
            }

            $missingRequirements[] = $requirement->requirement_name;
        }

        $matchPercentage = $totalWeight > 0
            ? (int) round(($weightedScore / $totalWeight) * 100)
            : (int) round(($verifiedMatchCount / $requirements->count()) * 100);

        $feedbackAdjustment = $this->calculateFeedbackAdjustment($feedback, $internship->id);
        $matchPercentage = max(0, min(100, $matchPercentage + $feedbackAdjustment));

        return [
            'match_percentage' => $matchPercentage,
            'details' => [
                'matched_requirements' => $matchedRequirements,
                'missing_requirements' => $missingRequirements,
                'verified_match_count' => $verifiedMatchCount,
                'total_requirements' => $requirements->count(),
                'explanation' => $verifiedMatchCount > 0
                    ? 'The recommendation score is based on verified competency evidence that meets the internship requirements.'
                    : 'No verified competency evidence currently matches the internship requirements.',
            ],
        ];
    }

    private function getStudentFeedback(Student $student): Collection
    {
        return RecommendationFeedback::where('student_id', $student->id)->get();
    }

    private function calculateFeedbackAdjustment($feedback, int $internshipId): int
    {
        if ($feedback->isEmpty()) {
            return 0;
        }

        $positiveCount = $feedback->where('feedback', 'helpful')->count();
        $negativeCount = $feedback->where('feedback', 'not_helpful')->count();
        $total = $feedback->count();

        if ($total === 0) {
            return 0;
        }

        $positiveRatio = $positiveCount / $total;

        return (int) round(($positiveRatio - 0.5) * 10);
    }
}
