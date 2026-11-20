<?php

namespace App\Services;

use App\Models\Coordinator;
use App\Models\EligibilityDecision;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/** Totals, rates, and per-program and per-company summaries for a coordinator's students. */
class InstitutionStatistics
{
    public function __construct(private InternshipEligibility $eligibility) {}

    public function for(Coordinator $coordinator): array
    {
        $studentIds = $coordinator->scopedStudentsQuery()->pluck('id');
        $placements = Placement::query()->whereIn('student_id', $studentIds);
        $latestDecisions = DB::table('eligibility_decisions')
            ->whereIn('student_id', $studentIds)
            ->whereIn('id', DB::table('eligibility_decisions')->selectRaw('max(id)')->groupBy('student_id'))
            ->pluck('decision');

        $enrolled = $studentIds->count();
        $placed = (clone $placements)->distinct()->count('student_id');
        $completed = (clone $placements)->where('status', Placement::STATUS_COMPLETED)->distinct()->count('student_id');

        $totals = [
            'enrolled' => $enrolled,
            'eligible' => $this->eligibility->constrain(Student::query()->whereIn('id', $studentIds))->count(),
            'approved' => $latestDecisions->filter(fn ($decision) => $decision === EligibilityDecision::APPROVED)->count(),
            'rejected' => $latestDecisions->filter(fn ($decision) => $decision === EligibilityDecision::REJECTED)->count(),
            'placed' => $placed,
            'pending_validation' => (clone $placements)->where('status', Placement::STATUS_PENDING_VALIDATION)->count(),
            'ongoing' => (clone $placements)->where('status', Placement::STATUS_ACTIVE)->count(),
            'completed' => $completed,
            'cleared' => (clone $placements)->whereNotNull('cleared_at')->count(),
        ];

        return [
            'totals' => $totals,
            'rates' => [
                'placement' => $enrolled ? round($placed / $enrolled * 100, 1) : 0.0,
                'completion' => $placed ? round($completed / $placed * 100, 1) : 0.0,
            ],
            'companies' => $this->byCompany($studentIds->all()),
            'generated_at' => now('Asia/Manila'),
        ];
    }

    /** Companies hosting these students, with the supervisors' average evaluation score. */
    private function byCompany(array $studentIds): array
    {
        return Placement::query()
            ->whereIn('placements.student_id', $studentIds)
            ->join('employers', 'employers.id', '=', 'placements.employer_id')
            ->leftJoin('evaluations', 'evaluations.placement_id', '=', 'placements.id')
            ->groupBy('employers.id', 'employers.company_name')
            ->orderBy('employers.company_name')
            ->get([
                'employers.company_name',
                DB::raw('count(distinct placements.id) as interns'),
                DB::raw("sum(case when placements.status = 'completed' then 1 else 0 end) as completed"),
                DB::raw('avg(evaluations.average_score) as average_score'),
                DB::raw('sum(case when evaluations.would_hire = 1 then 1 else 0 end) as would_hire'),
                DB::raw('count(evaluations.id) as evaluations'),
            ])
            ->map(fn ($row) => [
                'company' => $row->company_name,
                'interns' => (int) $row->interns,
                'completed' => (int) $row->completed,
                'average_score' => $row->average_score !== null ? round((float) $row->average_score, 2) : null,
                'would_hire' => (int) $row->evaluations ? round((int) $row->would_hire / (int) $row->evaluations * 100) : null,
            ])
            ->all();
    }
}
