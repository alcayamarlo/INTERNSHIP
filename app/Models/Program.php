<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
    protected $fillable = [
        'name',
        'code',
        'required_internship_hours',
        'required_hours_confirmed',
    ];

    protected function casts(): array
    {
        return ['required_hours_confirmed' => 'boolean'];
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    /** The department's one coordinator (coordinators.program_id is unique). */
    public function coordinator(): HasOne
    {
        return $this->hasOne(Coordinator::class);
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
