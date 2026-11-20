<?php

namespace App\Models;

use App\Services\OjtDocumentData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    protected $fillable = [
        'placement_id',
        'week_start',
        'week_number',
        'title',
        'accomplishments',
        'learnings',
        'challenges',
        'actions_taken',
        'daily_snapshot',
        'header_snapshot',
        'header_overrides',
        'status',
        'supervisor_comment',
        'reviewed_by_user_id',
        'reviewed_at',
        'coordinator_review_status',
        'coordinator_comment',
        'coordinator_reviewed_at',
        'coordinator_reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_number' => 'integer',
            'daily_snapshot' => 'array',
            'header_snapshot' => 'encrypted:array', // name, school ID, phone, address (App\Support\SensitiveColumns)
            'header_overrides' => 'array',
            'reviewed_at' => 'datetime',
            'coordinator_reviewed_at' => 'datetime',
        ];
    }

    /** The coordinator sent it back; the student may replace it. */
    public function isReturnedByCoordinator(): bool
    {
        return $this->coordinator_review_status === 'returned';
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    /** "Certified true and correct" by the supervisor (stored as acknowledged). */
    public function isCertified(): bool
    {
        return $this->isAcknowledged();
    }

    /**
     * The report's Inclusive Dates, e.g. "April 20 - 24, 2026": as printed when it was submitted, otherwise
     * the days it covers (weekdays, a weekend day with a time record, OJT dates only).
     */
    public function inclusiveDates(): string
    {
        $frozen = $this->header_snapshot['inclusive_dates'] ?? null;
        if (filled($frozen)) {
            return (string) $frozen;
        }

        return OjtDocumentData::inclusiveDates(...$this->placement->reportDateRange($this->week_start));
    }

    public static function titleFor(?int $weekNumber): string
    {
        return ($weekNumber ? 'Week '.$weekNumber : 'Weekly').' – Activity Narrative Report';
    }

    public function weekLabel(): string
    {
        return $this->week_start->format('M d').' – '.$this->week_start->copy()->addDays(6)->format('M d, Y');
    }
}
