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
    private const RECOMMENDATION_LIMITS = [3, 5, 10];

    public function calculateMatch(Student $student, Internship $internship): int
    {
        return $this->calculateMatchWithDetails($student, $internship)['match_percentage'];
    }

    /**
     * Same score as calculateMatch(), plus the breakdown of which requirements
     * matched and why each unmatched one is missing (no evidence, or below the
     * required proficiency level).
     */
    public function calculateMatchWithDetails(Student $student, Internship $internship): array
    {
        $cacheKey = "match_details_v3_{$student->id}_{$internship->id}";

        return Cache::remember($cacheKey, 3600, function () use ($student, $internship) {
            return $this->buildRecommendationResult($student, $internship);
        });
    }

    /**
     * Uncached score, for alerts sent right after a posting's requirements were saved
     * (the cached details may still hold the old requirements).
     */
    public function freshMatchPercentage(Student $student, Internship $internship): int
    {
        return $this->buildRecommendationResult($student, $internship)['match_percentage'];
    }

    public function getRecommendations(Student $student, int $limit = 10)
    {
        $cacheKey = "recommendations_v3_{$student->id}_{$limit}";
        $coordinator = $student->assignedCoordinator();
        // Read outside the cache so a posting the coordinator just marked unsuitable disappears at once.
        $flaggedIds = $coordinator
            ? \App\Models\InternshipFlag::query()->where('coordinator_id', $coordinator->id)->pluck('internship_id')->all()
            : [];

        return Cache::remember($cacheKey, 1800, function () use ($student, $limit, $coordinator) {
            // Recommendations stay within the student's program; searching covers every program.
            $internships = Internship::query()
                ->open()
                ->notFlaggedFor($coordinator)
                ->forProgram($student->program_id)
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
        })->reject(fn (Internship $internship) => in_array($internship->id, $flaggedIds, true))->values();
    }

    public function invalidateCache(Student $student): void
    {
        foreach (self::RECOMMENDATION_LIMITS as $limit) {
            Cache::forget("recommendations_v3_{$student->id}_{$limit}");
        }

        foreach (Internship::query()->pluck('id') as $internshipId) {
            Cache::forget("match_details_v3_{$student->id}_{$internshipId}");
        }
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
                    'matched_count' => 0,
                    'total_requirements' => 0,
                    'explanation' => 'This internship has no listed requirements, so no skill matching could be calculated.',
                ],
            ];
        }

        $studentCompetencies = $student->competencies()->with('learnedSkills')->get();
        $feedback = $this->getStudentFeedback($student, $internship->id);
        $matchedRequirements = [];
        $missingRequirements = [];
        $matchedCount = 0;
        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($requirements as $requirement) {
            $requiredScore = $requirement->required_level instanceof ProficiencyLevel
                ? $requirement->required_level->score()
                : ProficiencyLevel::from($requirement->required_level)->score();

            $weight = $requirement->is_required ? 2 : 1;
            $totalWeight += $weight;

            $requiredLevelLabel = $requirement->required_level instanceof ProficiencyLevel
                ? $requirement->required_level->label()
                : ProficiencyLevel::from($requirement->required_level)->label();

            $studentMatch = $this->evidenceFor($studentCompetencies, $requirement);

            if (! $studentMatch) {
                $missingRequirements[] = [
                    'name' => $requirement->requirement_name,
                    'required_level' => $requiredLevelLabel,
                    'student_level' => null,
                    'score' => 0,
                    'reason' => 'no_evidence',
                    'reason_label' => 'No evidence added for this skill yet.',
                ];
                continue;
            }

            $studentScore = $studentMatch->proficiency_level instanceof ProficiencyLevel
                ? $studentMatch->proficiency_level->score()
                : ProficiencyLevel::from($studentMatch->proficiency_level)->score();

            $studentLevelLabel = $studentMatch->proficiency_level instanceof ProficiencyLevel
                ? $studentMatch->proficiency_level->label()
                : ProficiencyLevel::from($studentMatch->proficiency_level)->label();
            // Level per skill: Beginner 1, Intermediate 2, Advanced 3 (Expert 4). Meeting or exceeding
            // the required level scores 100%; below it scores student level / required level.
            $skillScore = min($studentScore / $requiredScore, 1.0);

            if ($studentScore >= $requiredScore) {
                $matchedRequirements[] = [
                    'name' => $requirement->requirement_name,
                    'required_level' => $requiredLevelLabel,
                    'student_level' => $studentLevelLabel,
                    'score' => 100,
                ];
                $matchedCount++;
                $weightedScore += $weight * $skillScore;
                continue;
            }

            // Below the required level: partial credit, not a full match and not zero.
            $weightedScore += $weight * $skillScore;
            $missingRequirements[] = [
                'name' => $requirement->requirement_name,
                'required_level' => $requiredLevelLabel,
                'student_level' => $studentLevelLabel,
                'score' => (int) round($skillScore * 100),
                'reason' => 'below_required_level',
                'reason_label' => 'Your current level is below the required '.$requiredLevelLabel.' level.',
            ];
        }

        $matchPercentage = $totalWeight > 0
            ? (int) round(($weightedScore / $totalWeight) * 100)
            : (int) round(($matchedCount / $requirements->count()) * 100);

        $feedbackAdjustment = $this->calculateFeedbackAdjustment($feedback);
        $matchPercentage = max(0, min(100, $matchPercentage + $feedbackAdjustment));

        return [
            'match_percentage' => $matchPercentage,
            'details' => [
                'matched_requirements' => $matchedRequirements,
                'missing_requirements' => $missingRequirements,
                'matched_count' => $matchedCount,
                'total_requirements' => $requirements->count(),
                'explanation' => $matchedCount > 0
                    ? 'The recommendation score is based on competency evidence that meets the internship requirements.'
                    : 'No competency evidence currently matches the internship requirements.',
            ],
        ];
    }

    /**
     * The certificate that is evidence for a requirement: one linked to the same competency, one
     * whose title contains the requirement, or one whose title or "skills you learned" name it word
     * for word (see terms()). When several qualify, the highest proficiency level counts.
     */
    private function evidenceFor(Collection $competencies, $requirement): ?object
    {
        $wanted = self::terms((string) $requirement->requirement_name);
        $needle = strtolower(trim((string) $requirement->requirement_name));

        return $competencies
            ->filter(function ($competency) use ($requirement, $wanted, $needle) {
                if ($requirement->competency_id && $competency->competency_id === $requirement->competency_id) {
                    return true;
                }
                if ($needle !== '' && str_contains(strtolower((string) $competency->name), $needle)) {
                    return true;
                }
                if ($wanted === []) {
                    return false;
                }

                return collect([(string) $competency->name])
                    ->merge($competency->learnedSkills->pluck('name'))
                    ->contains(fn ($name) => array_diff($wanted, self::terms((string) $name)) === []);
            })
            ->sortByDesc(fn ($competency) => $competency->proficiency_level instanceof ProficiencyLevel
                ? $competency->proficiency_level->score()
                : (ProficiencyLevel::tryFrom((string) $competency->proficiency_level)?->score() ?? 0))
            ->first();
    }

    /**
     * The words that identify a skill name: lowercase stems without filler words, so
     * "Networking Configuration" covers "Network Configuration" and "IP Knowledge" is met by
     * "IP Addressing & Subnetting".
     *
     * @return list<string>
     */
    private static function terms(string $text): array
    {
        $filler = ['and', 'or', 'of', 'the', 'in', 'on', 'for', 'with', 'to', 'a', 'an', 'skill', 'knowledge', 'basic', 'fundamental'];
        $words = preg_split('/[^a-z0-9+#]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stems = array_map(function (string $word): string {
            if (strlen($word) > 5 && str_ends_with($word, 'ing')) {
                return substr($word, 0, -3);
            }
            if (strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
                return substr($word, 0, -1);
            }

            return $word;
        }, $words);

        return array_values(array_unique(array_diff($stems, $filler)));
    }

    private function getStudentFeedback(Student $student, int $internshipId): Collection
    {
        return RecommendationFeedback::where('student_id', $student->id)
            ->where('internship_id', $internshipId)
            ->get();
    }

    private function calculateFeedbackAdjustment(Collection $feedback): int
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
