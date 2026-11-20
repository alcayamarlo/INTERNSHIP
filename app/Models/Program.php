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

    /** BSHM keeps its own rule (any semester); every other program may only be eligible in the 2nd semester. */
    public const ANY_SEMESTER_CODE = 'BSHM';

    public const ELIGIBLE_TERM = 'second';

    public function limitedToSecondSemester(): bool
    {
        return strtoupper((string) $this->code) !== self::ANY_SEMESTER_CODE;
    }

    /** The department's first coordinator (one per program: see coordinators()). */
    public function coordinator(): HasOne
    {
        return $this->hasOne(Coordinator::class)->oldestOfMany();
    }

    /** Every coordinator of the department, at most Coordinator::MAX_PER_DEPARTMENT. */
    public function coordinators(): HasMany
    {
        return $this->hasMany(Coordinator::class);
    }

    /** Names of the department's coordinators, "A and B", or null when there is none (needs coordinators.user loaded). */
    public function coordinatorNames(): ?string
    {
        $names = $this->coordinators->map(fn (Coordinator $coordinator) => $coordinator->user?->name)->filter()->values();

        return $names->isEmpty() ? null : $names->join(', ', ' and ');
    }

    /** Departments that can still take a coordinator. */
    public function scopeAcceptingCoordinators($query)
    {
        return $query->has('coordinators', '<', Coordinator::MAX_PER_DEPARTMENT);
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
