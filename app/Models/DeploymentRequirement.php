<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeploymentRequirement extends Model
{
    public const SOURCE_STUDENT = 'student';
    public const SOURCE_COORDINATOR = 'coordinator';
    public const SOURCE_SYSTEM = 'system';

    protected $fillable = [
        'code',
        'name',
        'upload_source',
        'sort_order',
        'is_active',
        'on_request',
        'shared_with_employers',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'on_request' => 'boolean',
            'shared_with_employers' => 'boolean',
        ];
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Requirements every student needs, excluding IDs that are only uploaded when an internship asks. */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('on_request', false);
    }

    public function scopeOnRequest(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('on_request', true);
    }
}
