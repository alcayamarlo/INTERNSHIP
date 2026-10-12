<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'name',
        'code',
        'required_internship_hours',
        'required_hours_confirmed',
        'coordinator_id',
    ];

    protected function casts(): array
    {
        return ['required_hours_confirmed' => 'boolean'];
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
