<?php

namespace App\Services;

use App\Models\AcademicSetting;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

class AcademicCalendar
{
    public function termOptions(?AcademicSetting $settings = null): array
    {
        $settings ??= AcademicSetting::current();
        $options = $this->termLabels();

        if (! $settings->summer_enabled) {
            unset($options['summer']);
        }

        return $options;
    }

    public function termLabels(): array
    {
        return ['first' => '1st semester', 'second' => '2nd semester', 'summer' => 'Summer'];
    }

    public function schoolYears(?CarbonImmutable $date = null, ?AcademicSetting $settings = null): array
    {
        $date ??= CarbonImmutable::now();
        $settings ??= AcademicSetting::current();
        $startYear = $date->month >= (int) $settings->academic_year_start_month
            ? $date->year
            : $date->year - 1;

        return collect(range($startYear - 1, $startYear + 3))
            ->map(fn (int $year) => $year.'-'.($year + 1))
            ->all();
    }

    public function defaults(?CarbonImmutable $date = null, ?AcademicSetting $settings = null): array
    {
        $date ??= CarbonImmutable::now();
        $settings ??= AcademicSetting::current();
        $startYear = $date->month >= (int) $settings->academic_year_start_month
            ? $date->year
            : $date->year - 1;
        $term = $this->termForDate($date, $settings);

        return [
            'school_year' => $startYear.'-'.($startYear + 1),
            'semester' => $settings->current_term_override ?: $term,
        ];
    }

    public function termForDate(CarbonImmutable $date, AcademicSetting $settings): string
    {
        $monthDay = $date->format('m-d');
        $ranges = $settings->termRanges();

        foreach ($ranges as $term => $range) {
            $start = sprintf('%02d-%02d', $range['start_month'], $range['start_day']);
            $end = sprintf('%02d-%02d', $range['end_month'], $range['end_day']);
            $matches = $start <= $end
                ? $monthDay >= $start && $monthDay <= $end
                : $monthDay >= $start || $monthDay <= $end;

            if ($matches) {
                if ($term === 'summer' && ! $settings->summer_enabled) {
                    return 'second';
                }

                return $term;
            }
        }

        throw new \UnexpectedValueException('Academic term ranges do not cover the calendar date.');
    }

    public function rangesAreContinuous(array $ranges): bool
    {
        foreach ([2023, 2024] as $year) {
            $day = new DateTimeImmutable($year.'-01-01');
            $yearEnd = new DateTimeImmutable($year.'-12-31');

            while ($day <= $yearEnd) {
                $dateKey = $day->format('m-d');
                $matches = 0;

                foreach ($ranges as $range) {
                    if (! $this->validRange($range)) {
                        return false;
                    }

                    $start = sprintf('%02d-%02d', $range['start_month'], $range['start_day']);
                    $end = sprintf('%02d-%02d', $range['end_month'], $range['end_day']);
                    $matches += $start <= $end
                        ? (int) ($dateKey >= $start && $dateKey <= $end)
                        : (int) ($dateKey >= $start || $dateKey <= $end);
                }

                if ($matches !== 1) {
                    return false;
                }

                $day = $day->modify('+1 day');
            }
        }

        return true;
    }

    private function validRange(array $range): bool
    {
        foreach (['start_month', 'start_day', 'end_month', 'end_day'] as $key) {
            if (! isset($range[$key]) || ! is_numeric($range[$key])) {
                return false;
            }
        }

        foreach (['start', 'end'] as $side) {
            $date = sprintf('2024-%02d-%02d', $range[$side.'_month'], $range[$side.'_day']);

            if (! checkdate((int) $range[$side.'_month'], (int) $range[$side.'_day'], 2024)) {
                return false;
            }
        }

        return true;
    }
}
