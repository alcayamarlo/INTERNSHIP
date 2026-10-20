<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'placement_id',
        'work_date',
        'time_in',
        'time_out',
        'break_minutes',
        'hours',
        'task_summary',
        'status',
        'reviewer_remarks',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'break_minutes' => 'integer',
            'hours' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public static function computeHours(string $timeIn, string $timeOut, int $breakMinutes = 0): float
    {
        $minutes = Carbon::parse($timeIn)->diffInMinutes(Carbon::parse($timeOut), false) - $breakMinutes;

        return round(max(0, $minutes) / 60, 2);
    }

    public function timeInLabel(): string
    {
        return Carbon::parse($this->time_in)->format('g:i A');
    }

    public function timeOutLabel(): string
    {
        return Carbon::parse($this->time_out)->format('g:i A');
    }
}
