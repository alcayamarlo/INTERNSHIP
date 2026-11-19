<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coordinator extends Model
{
    /** A department has at most this many coordinators (both see the same students). */
    public const MAX_PER_DEPARTMENT = 2;

    public const DEPARTMENT_TAKEN = 'This department already has 2 coordinators.';

    protected $fillable = [
        'user_id',
        'institution_id',
        // The one department (program) this coordinator handles; a department has up to 2 coordinators. Only an admin sets it.
        'program_id',
        'office_address',
        'profile_picture',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** The coordinator's department (a program such as BSIT). */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** "BS Information Technology (BSIT)", or null until an admin assigns a department. */
    public function departmentName(): ?string
    {
        return $this->program?->display_name;
    }

    public function verificationActivities(): HasMany
    {
        return $this->hasMany(VerificationActivity::class);
    }

    /**
     * Every student this coordinator may see: same school and the coordinator's own department only.
     * A coordinator without a department sees no students.
     */
    public function scopedStudentsQuery(): Builder
    {
        if ($this->institution_id === null || $this->program_id === null) {
            return Student::query()->whereRaw('0 = 1');
        }

        return Student::query()
            ->where('institution_id', $this->institution_id)
            ->where('program_id', $this->program_id);
    }

    /** Whether the department can take (another) coordinator; $except is a coordinator who already has it (an edit). */
    public static function departmentHasRoom(int $programId, ?int $except = null): bool
    {
        return static::query()->where('program_id', $programId)
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except))
            ->count() < self::MAX_PER_DEPARTMENT;
    }

    public function canAccessStudent(Student $student): bool
    {
        return $this->institution_id !== null
            && $this->program_id !== null
            && (int) $student->institution_id === (int) $this->institution_id
            && (int) $student->program_id === (int) $this->program_id;
    }
}
