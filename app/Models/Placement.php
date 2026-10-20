<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Placement extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'application_id',
        'student_id',
        'employer_id',
        'internship_id',
        'start_date',
        'end_date',
        'required_hours',
        'status',
        'completed_at',
        'cleared_at',
        'cleared_by_user_id',
        'clearance_remarks',
        'certificate_number',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'required_hours' => 'integer',
            'completed_at' => 'datetime',
            'cleared_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(InternshipApplication::class, 'application_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by_user_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCleared(): bool
    {
        return $this->cleared_at !== null;
    }

    public function approvedHours(): float
    {
        if (array_key_exists('approved_hours', $this->attributes)) {
            return round((float) $this->attributes['approved_hours'], 2);
        }

        return round((float) $this->attendanceLogs()->where('status', AttendanceLog::STATUS_APPROVED)->sum('hours'), 2);
    }

    public function pendingHours(): float
    {
        return round((float) $this->attendanceLogs()->where('status', AttendanceLog::STATUS_PENDING)->sum('hours'), 2);
    }

    public function progressPercent(): int
    {
        if ($this->required_hours <= 0) {
            return 100;
        }

        return (int) min(100, floor($this->approvedHours() / $this->required_hours * 100));
    }

    public function remainingHours(): float
    {
        return max(0, round($this->required_hours - $this->approvedHours(), 2));
    }

    public function hasMetRequiredHours(): bool
    {
        return $this->approvedHours() >= $this->required_hours;
    }

    public function scopeWithApprovedHours($query)
    {
        return $query->withSum(
            ['attendanceLogs as approved_hours' => fn ($logs) => $logs->where('status', AttendanceLog::STATUS_APPROVED)],
            'hours'
        );
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isCleared() => 'Cleared',
            $this->isCompleted() => 'Completed',
            default => 'Ongoing',
        };
    }
}
