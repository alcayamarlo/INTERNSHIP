<?php

namespace App\Services;

use App\Enums\ProficiencyLevel;
use App\Models\Student;
use App\Models\StudentCompetency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Data for the My Certificates page (stored as student_competencies). The chart axes come
 * from config('competencies.groups'): Technical / Soft skills by skill type, Certificates by type.
 */
class StudentCompetencyOverviewService
{
    public function forStudent(Student $student, ?string $categoryFilter = null): array
    {
        $competencies = $student->competencies()->with('learnedSkills')->orderBy('name')->get();
        $groups = config('competencies.groups', []);
        $skills = $competencies
            ->map(fn (StudentCompetency $competency) => $this->presentSkill($competency, $groups))
            ->values();

        $unmappedSkills = $skills->filter(fn (array $skill) => $skill['group_keys'] === []);
        foreach ($unmappedSkills as $skill) {
            Log::warning('Certificate has no chart group in overview scoring.', [
                'student_id' => $student->id,
                'skill_id' => $skill['id'],
                'skill_type' => $skill['skill_type_key'],
                'category' => $skill['legacy_category'],
            ]);
        }

        $defaultTarget = (int) config('competencies.default_target', 4);
        $targets = config('competencies.targets', []);
        $groupStats = collect($groups)
            ->mapWithKeys(fn (array $group, string $key): array => [
                $key => $this->scoreSet(
                    $key,
                    $group['label'],
                    $skills->filter(fn (array $skill) => in_array($key, $skill['group_keys'], true))->values(),
                    (int) ($targets[$key] ?? $defaultTarget)
                ),
            ])
            ->all();

        $selectedSkills = $skills->filter(fn (array $skill): bool => ! $categoryFilter
            || in_array($categoryFilter, $skill['group_keys'], true)
            || ($categoryFilter === '__other' && $skill['group_keys'] === []))->values();

        $averagePoints = $selectedSkills->isEmpty()
            ? null
            : (float) $selectedSkills->avg('proficiency_points');

        return [
            'student_id' => $student->id,
            'category_filter' => $categoryFilter,
            'skills' => $skills->all(),
            'groups' => $groupStats,
            'profile_strength' => [
                'average_points' => $averagePoints,
                'average_level' => $averagePoints === null ? null : $averagePoints / 25,
                'level_label' => $this->levelLabel($averagePoints),
                'counted_skill_count' => $selectedSkills->count(),
            ],
            'unmapped_categories' => $unmappedSkills->pluck('legacy_category')->unique()->values()->all(),
        ];
    }

    private function presentSkill(StudentCompetency $competency, array $groups): array
    {
        $proficiency = $competency->proficiency_level;
        $groupKeys = collect($groups)
            ->filter(fn (array $group) => in_array(
                (string) $competency->getRawOriginal($group['field'] ?? ''),
                $group['values'] ?? [],
                true
            ))
            ->keys()
            ->values()
            ->all();
        $learnedSkills = $competency->learnedSkills->pluck('name')->values()->all();

        return [
            'id' => $competency->id,
            'name' => $competency->name,
            'type_key' => $competency->certificate_type?->value,
            'type_label' => $competency->certificate_type?->label() ?? 'Certificate',
            'skill_type_key' => $competency->skill_type?->value,
            'skill_type_label' => $competency->skill_type?->label(),
            // Kept for older readers of this payload.
            'category_key' => $competency->certificate_type?->value,
            'category_label' => $competency->certificate_type?->label() ?? 'Certificate',
            'group_key' => $competency->skill_type?->value,
            'legacy_category' => (string) $competency->getRawOriginal('category'),
            'group_keys' => $groupKeys,
            'proficiency_key' => $proficiency?->value,
            'proficiency_label' => $proficiency?->label() ?? 'Unknown',
            'proficiency_points' => $proficiency?->score() ?? 0,
            'learned_skills' => $learnedSkills,
            'evidence_filename' => $competency->evidence_name
                ?: ($competency->evidence_path ? basename($competency->evidence_path) : null),
            'file_url' => $competency->evidence_path ? route('student.competencies.file', $competency, false) : null,
            'show_url' => route('student.competencies.show', $competency, false),
            'update_url' => route('student.competencies.update', $competency, false),
            'delete_url' => route('student.competencies.destroy', $competency, false),
            'description' => $competency->description,
            'issuing_organization' => $competency->issuing_organization,
            'obtained_at' => $competency->obtained_at?->toDateString(),
            // Prefills the wizard when editing.
            'form' => [
                'certificate_type' => $competency->certificate_type?->value,
                'name' => $competency->name,
                'skill_type' => $competency->skill_type?->value,
                'proficiency_level' => $proficiency?->value,
                'skills' => $learnedSkills,
                'issuing_organization' => $competency->issuing_organization,
                'obtained_at' => $competency->obtained_at?->toDateString(),
                'description' => $competency->description,
                'evidence_name' => $competency->evidence_name ?: ($competency->evidence_path ? basename($competency->evidence_path) : null),
            ],
        ];
    }

    private function scoreSet(string $key, string $label, Collection $skills, int $target): array
    {
        $safeTarget = max(0, $target);

        return [
            'key' => $key,
            'label' => $label,
            'target' => $target,
            'score' => $this->percentage($skills, $safeTarget),
            'count' => $skills->count(),
            'points' => (int) $skills->sum('proficiency_points'),
        ];
    }

    private function percentage(Collection $skills, int $target): float
    {
        if ($target <= 0 || $skills->isEmpty()) {
            return 0.0;
        }

        return min(1.0, (float) $skills->sum('proficiency_points') / ($target * 100)) * 100;
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
