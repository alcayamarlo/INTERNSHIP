<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's report of a posting, company, message, or user. Only administrators see reports.
 */
class ContentReport extends Model
{
    public const TYPES = [
        'internship' => Internship::class,
        'company' => Employer::class,
        'message' => Message::class,
        'user' => User::class,
    ];

    public const TYPE_LABELS = [
        'internship' => 'Internship posting',
        'company' => 'Company',
        'message' => 'Message',
        'user' => 'User',
    ];

    public const REASONS = [
        'spam' => 'Spam or advertising',
        'scam' => 'Scam or fake opportunity',
        'inappropriate' => 'Inappropriate or offensive content',
        'harassment' => 'Harassment or abuse',
        'misleading' => 'Misleading or false information',
        'other' => 'Something else',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_ACTIONED = 'actioned';

    /** What an administrator did. "hide" applies to postings and messages only. */
    public const RESOLUTIONS = [
        'dismiss' => 'No action (dismiss)',
        'hide' => 'Hide the content',
        'warn' => 'Warn the owner',
        'suspend' => 'Suspend the owner',
    ];

    protected $fillable = [
        'reporter_user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'resolution',
        'admin_notes',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /** The reported item, or null if it no longer exists. */
    public function target(): ?Model
    {
        $class = self::TYPES[$this->reportable_type] ?? null;

        return $class ? $class::query()->find($this->reportable_id) : null;
    }

    /** The account responsible for the reported item. */
    public static function ownerOf(string $type, ?Model $target): ?User
    {
        return match (true) {
            $target === null => null,
            $type === 'internship' => $target->employer?->user,
            $type === 'company' => $target->user,
            $type === 'message' => $target->sender,
            $type === 'user' => $target,
            default => null,
        };
    }

    /** A short, readable description of the reported item for the admin queue. */
    public static function describe(string $type, ?Model $target): string
    {
        return match (true) {
            $target === null => 'Removed '.strtolower(self::TYPE_LABELS[$type] ?? 'item'),
            $type === 'internship' => $target->title.' at '.($target->employer?->company_name ?? 'unknown company'),
            $type === 'company' => $target->company_name,
            $type === 'message' => '"'.\Illuminate\Support\Str::limit($target->body, 80).'" from '.($target->sender?->name ?? 'unknown'),
            $type === 'user' => $target->name.' ('.$target->role?->label().')',
            default => 'Item',
        };
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
