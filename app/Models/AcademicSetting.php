<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AcademicSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $fillable = [
        'student_id_pattern',
        'require_fourth_year_standing',
        'require_major_subjects_passed',
        'academic_year_start_month',
        'summer_enabled',
        'current_term_override',
        'first_semester_start_month',
        'first_semester_start_day',
        'first_semester_end_month',
        'first_semester_end_day',
        'second_semester_start_month',
        'second_semester_start_day',
        'second_semester_end_month',
        'second_semester_end_day',
        'summer_start_month',
        'summer_start_day',
        'summer_end_month',
        'summer_end_day',
        'required_ojt_hours',
        'evaluation_period_labels',
    ];

    protected function casts(): array
    {
        return [
            'academic_year_start_month' => 'integer',
            'required_ojt_hours' => 'integer',
            'summer_enabled' => 'boolean',
            'require_fourth_year_standing' => 'boolean',
            'require_major_subjects_passed' => 'boolean',
            'evaluation_period_labels' => 'array',
        ];
    }

    /** Month number (1-12) => evaluation period name, falling back to the defaults (Jan Prelim ... Apr Finals). */
    public function evaluationPeriodLabels(): array
    {
        $saved = is_array($this->evaluation_period_labels) ? $this->evaluation_period_labels : null;
        $labels = [];
        foreach (range(1, 12) as $month) {
            $value = $saved === null ? (Appraisal::DEFAULT_PERIOD_LABELS[$month] ?? '') : (string) ($saved[$month] ?? $saved[(string) $month] ?? '');
            $labels[$month] = trim($value);
        }

        return $labels;
    }

    /** The name printed as "Period of Evaluation" for a month: its configured name, or the month name. */
    public function evaluationPeriodLabelFor(int $month): string
    {
        return $this->evaluationPeriodLabels()[$month] ?: Carbon::create(2000, $month, 1)->format('F');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => self::SINGLETON_ID])->refresh();
    }

    public function termRanges(): array
    {
        return [
            'first' => [
                'start_month' => (int) $this->first_semester_start_month,
                'start_day' => (int) $this->first_semester_start_day,
                'end_month' => (int) $this->first_semester_end_month,
                'end_day' => (int) $this->first_semester_end_day,
            ],
            'second' => [
                'start_month' => (int) $this->second_semester_start_month,
                'start_day' => (int) $this->second_semester_start_day,
                'end_month' => (int) $this->second_semester_end_month,
                'end_day' => (int) $this->second_semester_end_day,
            ],
            'summer' => [
                'start_month' => (int) $this->summer_start_month,
                'start_day' => (int) $this->summer_start_day,
                'end_month' => (int) $this->summer_end_month,
                'end_day' => (int) $this->summer_end_day,
            ],
        ];
    }
}
