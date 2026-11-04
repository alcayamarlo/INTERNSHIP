<?php

namespace App\Services;

use App\Models\DeploymentRequirement;
use App\Models\EligibilityDecision;
use App\Models\Student;

/**
 * Checks a student against the criteria that apply to them and returns a pass/fail line per rule,
 * plus the coordinator's latest decision (a rejection blocks applying; an approval clears it).
 */
class EligibilityChecker
{
    /** Document statuses that count as submitted. */
    public const SUBMITTED_STATUSES = ['submitted', 'resubmitted', 'pending', 'approved', 'verified'];

    public function __construct(private EligibilityCriteria $criteria) {}

    /**
     * @return array{eligible: bool, source: string, rules: list<array{key: string, label: string, passed: bool, detail: string, reason: ?string}>, decision: ?EligibilityDecision}
     */
    public function check(Student $student): array
    {
        $rules = $this->criteria->forStudent($student);
        $lines = [];

        if ($rules['require_fourth_year']) {
            $passed = $student->year_level === '4th Year';
            $lines[] = $this->line('fourth_year', '4th Year standing', $passed, $student->year_level ?: 'Year level not set', 'You must be in 4th Year to apply.');
        }

        if ($rules['require_major_subjects_passed']) {
            $passed = (bool) $student->major_subjects_passed;
            $lines[] = $this->line('major_subjects', 'Major subjects passed', $passed, $passed ? 'Confirmed by the coordinator' : 'Not confirmed yet', 'Your coordinator must confirm that all major subjects are passed.');
        }

        if ($rules['max_gwa'] !== null) {
            $required = number_format($rules['max_gwa'], 2);
            $passed = $student->gwa !== null && (float) $student->gwa <= $rules['max_gwa'];
            $lines[] = $this->line(
                'gwa',
                'GWA '.$required.' or better',
                $passed,
                $student->gwa === null ? 'GWA not on record' : 'GWA '.number_format((float) $student->gwa, 2),
                $student->gwa === null
                    ? 'Add your GWA on your profile. Your program requires a GWA of '.$required.' or better.'
                    : 'Your program requires a GWA of '.$required.' or better.'
            );
        }

        if ($rules['required_document_ids'] !== []) {
            $missing = $this->missingDocuments($student, $rules['required_document_ids']);
            $lines[] = $this->line(
                'documents',
                'Required documents',
                $missing === [],
                $missing === [] ? 'All submitted' : 'Missing: '.implode(', ', $missing),
                'Upload these documents first: '.implode(', ', $missing).'.'
            );
        }

        $decision = $student->latestEligibilityDecision();
        if ($decision?->isRejected()) {
            $lines[] = $this->line('decision', 'Coordinator decision', false, 'Not eligible: '.$decision->reason, 'Your coordinator marked you not eligible: '.$decision->reason);
        }

        return [
            'eligible' => collect($lines)->every(fn (array $line) => $line['passed']),
            'source' => $rules['source'],
            'rules' => $lines,
            'decision' => $decision,
        ];
    }

    /** @return list<string> reasons for the rules that failed */
    public function reasons(Student $student): array
    {
        return collect($this->check($student)['rules'])->reject(fn (array $line) => $line['passed'])->pluck('reason')->values()->all();
    }

    /** @return list<string> names of required documents not submitted yet */
    private function missingDocuments(Student $student, array $documentIds): array
    {
        $submitted = $student->deploymentDocuments()
            ->whereIn('deployment_requirement_id', $documentIds)
            ->whereIn('verification_status', self::SUBMITTED_STATUSES)
            ->pluck('deployment_requirement_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return DeploymentRequirement::query()
            ->whereIn('id', array_diff($documentIds, $submitted))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();
    }

    private function line(string $key, string $label, bool $passed, string $detail, string $reason): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => $passed, 'detail' => $detail, 'reason' => $passed ? null : $reason];
    }
}
