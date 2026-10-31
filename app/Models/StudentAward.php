<?php

namespace App\Models;

use App\Enums\AwardLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class StudentAward extends Model
{
    /** "2024", "2023-2024", or "2023-present". */
    public const YEAR_PATTERN = '/^\d{4}(\s*[-–]\s*(\d{4}|present))?$/i';

    protected $fillable = ['student_id', 'level', 'title', 'event', 'year', 'detail'];

    protected function casts(): array
    {
        return [
            'level' => AwardLevel::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Smaller line under the title: event, year, and detail. */
    public function metaLine(): string
    {
        return collect([$this->event, $this->year, $this->detail])->filter(fn ($value) => filled($value))->implode(' · ');
    }

    public function formValues(): array
    {
        return [
            'level' => $this->level?->value,
            'title' => $this->title,
            'event' => $this->event,
            'year' => $this->year,
            'detail' => $this->detail,
        ];
    }

    /**
     * Awards grouped by level in display order, newest year first inside each group.
     * Empty groups are left out.
     *
     * @param  Collection<int, StudentAward>  $awards
     * @return list<array{level: string, label: string, awards: list<StudentAward>}>
     */
    public static function grouped(Collection $awards): array
    {
        $groups = [];
        foreach (AwardLevel::cases() as $level) {
            $inLevel = $awards->filter(fn (StudentAward $award) => $award->level === $level)
                ->sort(fn (StudentAward $a, StudentAward $b) => $b->sortKey() <=> $a->sortKey() ?: $b->id <=> $a->id)
                ->values();

            if ($inLevel->isNotEmpty()) {
                $groups[] = ['level' => $level->value, 'label' => $level->label(), 'awards' => $inLevel->all()];
            }
        }

        return $groups;
    }

    /**
     * [latest year, first year]: "present" counts as the latest; no year sorts last.
     *
     * @return array{0: int, 1: int}
     */
    private function sortKey(): array
    {
        if (! preg_match_all('/\d{4}|present/i', (string) $this->year, $matches)) {
            return [0, 0];
        }

        $years = array_map(fn (string $part) => strcasecmp($part, 'present') === 0 ? 9999 : (int) $part, $matches[0]);

        return [max($years), min($years)];
    }
}
