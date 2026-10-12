<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coordinator extends Model
{
    protected $fillable = [
        'user_id',
        'institution_id',
        'department',
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

    public function verificationActivities(): HasMany
    {
        return $this->hasMany(VerificationActivity::class);
    }

    public function scopedStudentsQuery(): Builder
    {
        if ($this->institution_id === null) {
            return Student::query()->whereRaw('0 = 1');
        }

        return Student::query()
            ->where('institution_id', $this->institution_id)
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('academicProgram')
                    ->orWhereHas('academicProgram', fn (Builder $programQuery) => $programQuery
                        ->whereNull('coordinator_id')
                        ->orWhere('coordinator_id', $this->id));
            });
    }

    public function canAccessStudent(Student $student): bool
    {
        if ($this->institution_id === null || (int) $student->institution_id !== (int) $this->institution_id) {
            return false;
        }

        $assignedCoordinatorId = $student->academicProgram?->coordinator_id;

        return $assignedCoordinatorId === null || (int) $assignedCoordinatorId === (int) $this->id;
    }
}
