<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function scopedStudentsQuery(): Builder
    {
        $query = Student::query();

        if ($this->institution_id === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('institution_id', $this->institution_id);
    }

    public function canAccessStudent(Student $student): bool
    {
        if ($this->institution_id === null) {
            return false;
        }

        return (int) $student->institution_id === (int) $this->institution_id;
    }
}
