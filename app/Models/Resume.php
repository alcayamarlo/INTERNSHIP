<?php

namespace App\Models;

use App\Enums\PortfolioCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('not_archived', fn (Builder $query) => $query->whereNull('archived_at'));
    }

    protected $fillable = [
        'student_id',
        'content',
        'file_path',
        'category',
        'uploaded_by_user_id',
        'generated_at',
        'verification_status',
        'review_notes',
        'reviewed_at',
        'reviewed_by_user_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'generated_at' => 'datetime',
            'category' => PortfolioCategory::class,
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeWithArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_archived');
    }

    public function archive(): void
    {
        if (! $this->archived_at) {
            $this->update(['archived_at' => now()]);
        }
    }

    public function restore(): void
    {
        if ($this->archived_at) {
            $this->update(['archived_at' => null]);
        }
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
