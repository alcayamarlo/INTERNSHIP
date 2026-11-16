<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Placement extends Model
{
    use SoftDeletes;

    /** Accepted by the company; waits for the coordinator to validate it before hours can be logged. */
    public const STATUS_PENDING_VALIDATION = 'pending_validation';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    /** No time logged for this many days on an active OJT marks it at risk. */
    public const AT_RISK_IDLE_DAYS = 7;

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
        'certificate_snapshot',
        'rating_requested_at',
        'rating_requested_by_user_id',
        'rating_due_on',
        'department',
        'supervisor_name',
        'validated_at',
        'validated_by_user_id',
        'validation_remarks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'certificate_snapshot' => 'array',
            'required_hours' => 'integer',
            'completed_at' => 'datetime',
            'cleared_at' => 'datetime',
            'rating_requested_at' => 'datetime',
            'rating_due_on' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    public function isPendingValidation(): bool
    {
        return $this->status === self::STATUS_PENDING_VALIDATION;
    }

    /**
     * Active OJTs that need attention: started over a week ago with no time logged in the last week,
     * or past their end date without the required hours.
     */
    public function scopeAtRisk($query)
    {
        $since = now()->subDays(self::AT_RISK_IDLE_DAYS)->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)->where(fn ($risk) => $risk
            ->where(fn ($idle) => $idle->whereDate('start_date', '<=', $since)
                ->whereDoesntHave('attendanceLogs', fn ($logs) => $logs->whereDate('work_date', '>=', $since)))
            ->orWhere(fn ($late) => $late->whereNotNull('end_date')->whereDate('end_date', '<', now()->toDateString())));
    }

    /** Why this OJT is at risk, or null. */
    public function riskReason(): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        if ($this->end_date && $this->end_date->isPast() && ! $this->hasMetRequiredHours()) {
            return 'Past the end date without the required hours';
        }

        $since = now()->subDays(self::AT_RISK_IDLE_DAYS)->startOfDay();
        $logged = $this->relationLoaded('attendanceLogs')
            ? $this->attendanceLogs->contains(fn ($log) => $log->work_date->gte($since))
            : $this->attendanceLogs()->whereDate('work_date', '>=', $since->toDateString())->exists();

        return $this->start_date && $this->start_date->lte($since) && ! $logged
            ? 'No time logged in the last '.self::AT_RISK_IDLE_DAYS.' days'
            : null;
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
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

    public function ratingRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rating_requested_by_user_id');
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

    /** Monthly Evaluations (Performance Appraisal Reports), one per month. */
    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Weekly report number for a date: week 1 is the Monday-to-Friday week containing the start date.
     * Returns null before the OJT starts or when there is no start date.
     */
    public function weekNumberFor(\DateTimeInterface $date): ?int
    {
        if (! $this->start_date) {
            return null;
        }
        $firstMonday = CarbonImmutable::parse($this->start_date)->startOfWeek();
        $monday = CarbonImmutable::parse($date)->startOfWeek();
        $number = intdiv((int) $firstMonday->diffInDays($monday, false), 7) + 1;

        return $number >= 1 ? $number : null;
    }

    /**
     * The days a weekly report covers, for the week (Monday to Sunday) that contains $day: Monday to
     * Friday, plus Saturday or Sunday only when time was logged that day (not rejected), and never a day
     * before the OJT start date or after its end date.
     *
     * @return list<CarbonImmutable>
     */
    public function reportDays(\DateTimeInterface $day): array
    {
        $monday = CarbonImmutable::parse($day->format('Y-m-d'))->startOfWeek();
        $loggedWeekend = $this->attendanceLogs()
            ->whereDate('work_date', '>=', $monday->addDays(5)->toDateString())
            ->whereDate('work_date', '<=', $monday->addDays(6)->toDateString())
            ->where('status', '!=', AttendanceLog::STATUS_REJECTED)
            ->pluck('work_date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->all();

        return self::coveredDays($monday, $this->start_date, $this->end_date, $loggedWeekend);
    }

    /**
     * The rule behind reportDays(), without the database.
     *
     * @param  list<string>  $loggedWeekend  Saturday/Sunday dates (Y-m-d) with a time record
     * @return list<CarbonImmutable>
     */
    public static function coveredDays(\DateTimeInterface $day, ?\DateTimeInterface $start, ?\DateTimeInterface $end, array $loggedWeekend): array
    {
        $monday = CarbonImmutable::parse($day->format('Y-m-d'))->startOfWeek();
        $start = $start ? CarbonImmutable::parse($start->format('Y-m-d')) : null;
        $end = $end ? CarbonImmutable::parse($end->format('Y-m-d')) : null;

        $days = [];
        for ($date = $monday; $date->lte($monday->addDays(6)); $date = $date->addDay()) {
            if (($start && $date->lt($start)) || ($end && $date->gt($end))) {
                continue;
            }
            if ($date->isWeekend() && ! in_array($date->toDateString(), $loggedWeekend, true)) {
                continue;
            }
            $days[] = $date;
        }

        return $days;
    }

    /** Inclusive Dates of a weekly report: its first to last covered day (or the first OJT day of that week). */
    public function reportDateRange(\DateTimeInterface $day): array
    {
        $days = $this->reportDays($day);
        if ($days === []) {
            $monday = CarbonImmutable::parse($day->format('Y-m-d'))->startOfWeek();
            $first = $this->start_date && $monday->lt($this->start_date) ? CarbonImmutable::parse($this->start_date->format('Y-m-d')) : $monday;

            return [$first, $first];
        }

        return [$days[0], $days[array_key_last($days)]];
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
        if (array_key_exists('pending_hours', $this->attributes)) {
            return round((float) $this->attributes['pending_hours'], 2);
        }

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

    /** Preloads pendingHours() for a list, like withApprovedHours() does for approved hours. */
    public function scopeWithPendingHours($query)
    {
        return $query->withSum(
            ['attendanceLogs as pending_hours' => fn ($logs) => $logs->where('status', AttendanceLog::STATUS_PENDING)],
            'hours'
        );
    }

    /** Status key for <x-status-badge>, matching statusLabel(). */
    public function statusKey(): string
    {
        return match (true) {
            $this->isCleared() => 'verified',
            $this->isCompleted() => 'completed',
            $this->isPendingValidation() => 'pending',
            default => 'active',
        };
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isCleared() => 'Cleared',
            $this->isCompleted() => 'Completed',
            $this->isPendingValidation() => 'Waiting for coordinator validation',
            default => 'Ongoing',
        };
    }
}
