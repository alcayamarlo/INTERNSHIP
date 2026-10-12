<?php

namespace App\Services;

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Models\Student;
use App\Models\StudentCompetency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentCompetencyOverviewService
{
    public function forStudent(Student $student, string $view = 'verified', ?string $categoryFilter = null): array
    {
        $view = in_array($view, ['verified', 'all'], true) ? $view : 'verified';
        $competencies = $student->competencies()->orderBy('name')->get();
        $groups = config('competencies.groups', []);
        $statusMap = config('competencies.status_map', []);
        $unmappedStatus = (string) config('competencies.unmapped_status', 'not_counted');
        $categoryGroups = $this->categoryGroups($groups);
        $skills = $competencies
            ->map(fn (StudentCompetency $competency) => $this->presentSkill($competency, $categoryGroups, $statusMap, $unmappedStatus))
            ->values();

        $unmappedCategorySkills = $skills->filter(fn (array $skill) => $skill['group_key'] === null);
        $unmappedCategories = $unmappedCategorySkills
            ->pluck('category_key')
            ->unique()
            ->values()
            ->all();

        foreach ($unmappedCategorySkills as $skill) {
            Log::warning('Unmapped student competency category in overview scoring.', [
                'student_id' => $student->id,
                'skill_id' => $skill['id'],
                'category' => $skill['category_key'],
            ]);
        }

        $unmappedStatusSkills = $skills->where('status_group', $unmappedStatus);
        foreach ($unmappedStatusSkills as $skill) {
            Log::warning('Unmapped student competency status in overview scoring.', [
                'student_id' => $student->id,
                'skill_id' => $skill['id'],
                'status' => $skill['status_key'],
            ]);
        }

        $defaultTarget = (int) config('competencies.default_target', 4);
        $targets = config('competencies.targets', []);
        $groupStats = collect($groups)
            ->mapWithKeys(function (array $group, string $key) use ($skills, $targets, $defaultTarget): array {
                $members = $skills
                    ->where('group_key', $key)
                    ->values();

                return [
                    $key => $this->scoreSet(
                        $key,
                        $group['label'],
                        $members,
                        (int) ($targets[$key] ?? $defaultTarget)
                    ),
                ];
            })
            ->all();

        $selectedSkills = $skills->filter(function (array $skill) use ($view, $categoryFilter): bool {
            return $this->countsInView($skill['status_group'], $view)
                && (! $categoryFilter
                    || $skill['category_key'] === $categoryFilter
                    || $skill['group_key'] === $categoryFilter);
        })->values();

        $averagePoints = $selectedSkills->isEmpty()
            ? null
            : (float) $selectedSkills->avg('proficiency_points');

        return [
            'student_id' => $student->id,
            'view' => $view,
            'category_filter' => $categoryFilter,
            'skills' => $skills->all(),
            'groups' => $groupStats,
            'profile_strength' => [
                'average_points' => $averagePoints,
                'average_level' => $averagePoints === null ? null : $averagePoints / 25,
                'level_label' => $this->levelLabel($averagePoints),
                'counted_skill_count' => $selectedSkills->count(),
            ],
            'unmapped' => [
                'count' => $unmappedStatusSkills->count(),
                'statuses' => $unmappedStatusSkills->pluck('status_key')->unique()->values()->all(),
                'category_count' => $unmappedCategorySkills->count(),
                'categories' => $unmappedCategories,
            ],
            'unmapped_categories' => $unmappedCategories,
        ];
    }

    private function presentSkill(
        StudentCompetency $competency,
        array $categoryGroups,
        array $statusMap,
        string $unmappedStatus
    ): array {
        $categoryKey = (string) $competency->getRawOriginal('category');
        $category = CompetencyCategory::tryFrom($categoryKey);
        $proficiencyKey = (string) $competency->getRawOriginal('proficiency_level');
        $proficiency = ProficiencyLevel::tryFrom($proficiencyKey);
        $status = strtolower(trim((string) $competency->getRawOriginal('verification_status')));
        $statusGroup = $statusMap[$status] ?? $unmappedStatus;
        $categoryLabel = $category?->label() ?? ($categoryKey === '' ? 'Other' : ucfirst(str_replace('_', ' ', $categoryKey)));

        return [
            'id' => $competency->id,
            'name' => $competency->name,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'group_key' => $categoryGroups[$categoryKey] ?? null,
            'proficiency_key' => $proficiency?->value,
            'proficiency_label' => $proficiency?->label() ?? 'Unknown',
            'proficiency_points' => $proficiency?->score() ?? 0,
            'status_key' => $status,
            'status_group' => $statusGroup,
            'status_label' => $this->statusLabel($statusGroup),
            'evidence_filename' => $competency->evidence_name
                ?: ($competency->evidence_path ? basename($competency->evidence_path) : null),
            'coordinator_note' => $competency->review_notes,
            'description' => $competency->description,
            'assessment_name' => $competency->assessment_name,
            'issuing_organization' => $competency->issuing_organization,
            'obtained_at' => $competency->obtained_at?->toDateString(),
            'counts_as_verified' => $statusGroup === 'verified',
            'counts_as_pending' => $statusGroup === 'pending',
            'counts_in_score' => in_array($statusGroup, ['verified', 'pending'], true),
        ];
    }

    private function scoreSet(string $key, string $label, Collection $skills, int $target): array
    {
        $verified = $skills
            ->filter(fn (array $skill) => $skill['status_group'] === 'verified');
        $pending = $skills
            ->filter(fn (array $skill) => $skill['status_group'] === 'pending');
        $rejected = $skills
            ->filter(fn (array $skill) => $skill['status_group'] === 'rejected');
        $unmapped = $skills
            ->filter(fn (array $skill) => $skill['status_group'] === (string) config('competencies.unmapped_status', 'not_counted'));
        $allSubmitted = $verified->concat($pending);
        $safeTarget = max(0, $target);

        return [
            'key' => $key,
            'label' => $label,
            'target' => $target,
            'scores' => [
                'verified' => $this->percentage($verified, $safeTarget),
                'all_submitted' => $this->percentage($allSubmitted, $safeTarget),
            ],
            'counts' => [
                'verified' => $verified->count(),
                'all_submitted' => $allSubmitted->count(),
                'pending' => $pending->count(),
                'rejected' => $rejected->count(),
                'unmapped' => $unmapped->count(),
            ],
        ];
    }

    private function percentage(Collection $skills, int $target): float
    {
        if ($target <= 0 || $skills->isEmpty()) {
            return 0.0;
        }

        return min(1.0, (float) $skills->sum('proficiency_points') / ($target * 100)) * 100;
    }

    private function countsInView(string $statusGroup, string $view): bool
    {
        if ($statusGroup === 'verified') {
            return true;
        }

        return $view === 'all' && $statusGroup === 'pending';
    }

    private function categoryGroups(array $groups): array
    {
        $categoryGroups = [];
        foreach ($groups as $groupKey => $group) {
            foreach ($group['categories'] ?? [] as $category) {
                $categoryGroups[$category] = $groupKey;
            }
        }

        return $categoryGroups;
    }

    private function statusLabel(string $statusGroup): string
    {
        return match ($statusGroup) {
            'verified' => 'Verified',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            default => 'Not counted',
        };
    }

    private function levelLabel(?float $averagePoints): string
    {
        if ($averagePoints === null) {
            return 'No counted skills';
        }

        return match (true) {
            $averagePoints < 37.5 => ProficiencyLevel::Beginner->label(),
            $averagePoints < 62.5 => ProficiencyLevel::Intermediate->label(),
            $averagePoints < 87.5 => ProficiencyLevel::Advanced->label(),
            default => ProficiencyLevel::Expert->label(),
        };
    }
}
