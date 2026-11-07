<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whether a student may apply, using the criteria that apply to them (see EligibilityCriteria:
 * their program's criteria for the current term, or the global academic settings).
 */
class InternshipEligibility
{
    public function __construct(private EligibilityCriteria $criteria, private EligibilityChecker $checker) {}

    /** @return list<string> why the student cannot apply (empty when they can) */
    public function reasons(Student $student): array
    {
        return $this->checker->reasons($student);
    }

    /** The same rules as reasons(), as a query filter on students. */
    public function constrain(Builder $students): Builder
    {
        $byProgram = $this->criteria->currentByProgram();
        $global = $this->criteria->globalRules();

        // A coordinator's latest decision being a rejection blocks the student.
        $students->whereNotExists(fn ($decisions) => $decisions->from('eligibility_decisions as d')
            ->whereColumn('d.student_id', 'students.id')
            ->where('d.decision', \App\Models\EligibilityDecision::REJECTED)
            ->whereRaw('d.id = (select max(d2.id) from eligibility_decisions d2 where d2.student_id = students.id)'));

        return $students->where(function (Builder $query) use ($byProgram, $global): void {
            foreach ($byProgram as $programId => $rules) {
                $query->orWhere(fn (Builder $match) => $this->applyRules($match->where('program_id', $programId), $rules));
            }

            $query->orWhere(fn (Builder $match) => $this->applyRules(
                $match->where(fn (Builder $program) => $program->whereNull('program_id')->orWhereNotIn('program_id', $byProgram->keys()->all())),
                $global
            ));
        });
    }

    private function applyRules(Builder $query, array $rules): Builder
    {
        return $query
            ->when($rules['require_fourth_year'], fn (Builder $q) => $q->where('year_level', '4th Year'))
            ->when($rules['require_major_subjects_passed'], fn (Builder $q) => $q->where('major_subjects_passed', true))
            ->when($rules['required_document_ids'] !== [], function (Builder $q) use ($rules) {
                foreach ($rules['required_document_ids'] as $documentId) {
                    $q->whereHas('deploymentDocuments', fn (Builder $documents) => $documents
                        ->where('deployment_requirement_id', $documentId)
                        ->whereIn('verification_status', EligibilityChecker::SUBMITTED_STATUSES));
                }
            });
    }
}
