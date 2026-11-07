<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\EligibilityCriterion;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which internship criteria apply. A program's latest criteria version for the current term wins;
 * programs without one fall back to the global academic settings (4th Year standing, major subjects).
 */
class EligibilityCriteria
{
    public function __construct(private AcademicCalendar $calendar) {}

    /** @return array{school_year: string, term: string} */
    public function currentTerm(): array
    {
        $defaults = $this->calendar->defaults();

        return ['school_year' => $defaults['school_year'], 'term' => $defaults['semester']];
    }

    public function latestFor(int $programId, string $schoolYear, string $term): ?EligibilityCriterion
    {
        return EligibilityCriterion::query()->forTerm($programId, $schoolYear, $term)->orderByDesc('version')->first();
    }

    /** The rules that apply to a student right now. */
    public function forStudent(Student $student): array
    {
        $term = $this->currentTerm();
        $criterion = $student->program_id ? $this->latestFor((int) $student->program_id, $term['school_year'], $term['term']) : null;

        return $criterion ? $this->rulesFrom($criterion) : $this->globalRules();
    }

    /**
     * Rules for every program that has criteria this term, keyed by program id; other programs use globalRules().
     *
     * @return Collection<int, array>
     */
    public function currentByProgram(): Collection
    {
        $term = $this->currentTerm();

        return EligibilityCriterion::query()
            ->where('school_year', $term['school_year'])
            ->where('term', $term['term'])
            ->orderBy('version')
            ->get()
            ->keyBy('program_id')
            ->map(fn (EligibilityCriterion $criterion) => $this->rulesFrom($criterion));
    }

    public function globalRules(): array
    {
        $settings = AcademicSetting::current();

        return [
            'source' => 'global',
            'criterion' => null,
            'require_fourth_year' => (bool) $settings->require_fourth_year_standing,
            'require_major_subjects_passed' => (bool) $settings->require_major_subjects_passed,
            'required_hours' => null,
            'required_document_ids' => [],
        ];
    }

    /** Saves a new version for the program and term; earlier versions are kept as history. */
    public function saveVersion(Program $program, string $schoolYear, string $term, array $values, User $author): EligibilityCriterion
    {
        return DB::transaction(function () use ($program, $schoolYear, $term, $values, $author) {
            $latest = EligibilityCriterion::query()->forTerm($program->id, $schoolYear, $term)->lockForUpdate()->max('version');

            return EligibilityCriterion::create([
                ...$values,
                'program_id' => $program->id,
                'school_year' => $schoolYear,
                'term' => $term,
                'version' => (int) $latest + 1,
                'created_by_user_id' => $author->id,
            ]);
        });
    }

    private function rulesFrom(EligibilityCriterion $criterion): array
    {
        return [
            'source' => 'program',
            'criterion' => $criterion,
            'require_fourth_year' => $criterion->require_fourth_year,
            'require_major_subjects_passed' => $criterion->require_major_subjects_passed,
            'required_hours' => $criterion->required_hours,
            'required_document_ids' => $criterion->requiredDocumentIds(),
        ];
    }
}
