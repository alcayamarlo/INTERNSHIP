<?php

namespace App\Models;

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
            'header_snapshot' => 'array',
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

    /** Monday to Friday of the reported week, e.g. "April 20 - 24, 2026". */
    public function inclusiveDates(): string
    {
        $monday = $this->week_start->copy()->startOfWeek();
        $friday = $monday->copy()->addDays(4);

        return $monday->isSameMonth($friday)
            ? $monday->format('F j').' - '.$friday->format('j, Y')
            : $monday->format('F j').' - '.$friday->format('F j, Y');
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
