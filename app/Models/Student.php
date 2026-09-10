<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'institution_id',
        'student_id_number',
        'program',
        'year_level',
        'career_objectives',
        'profile_picture',
        'address',
        'middle_name', 'suffix', 'gender', 'city', 'province', 'zip_code', 'department',
        'first_name', 'last_name',
        'expected_graduation', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location',
        'date_of_birth',
        'profile_completion',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'expected_graduation' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(StudentCompetency::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function calculateProfileCompletion(): int
    {
        $fields = [
            $this->student_id_number,
            $this->program,
            $this->year_level,
            $this->career_objectives,
            $this->address,
            $this->date_of_birth,
            $this->profile_picture,
            $this->user?->phone,
        ];

        $filled = collect($fields)->filter(fn ($value) => ! empty($value))->count();
        $competencyBonus = min($this->competencies()->count(), 3);
        $total = count($fields) + 3;
        $completion = (int) round((($filled + $competencyBonus) / $total) * 100);

        $this->update(['profile_completion' => $completion]);

        return $completion;
    }
}
