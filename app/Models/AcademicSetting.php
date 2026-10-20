<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'academic_year_start_month' => 'integer',
            'required_ojt_hours' => 'integer',
            'summer_enabled' => 'boolean',
            'require_fourth_year_standing' => 'boolean',
            'require_major_subjects_passed' => 'boolean',
        ];
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
