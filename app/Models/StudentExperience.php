<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExperience extends Model
{
    protected $fillable = ['student_id', 'organization', 'position', 'start_date', 'end_date', 'is_current', 'description'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Values the profile page uses to prefill the edit form. */
    public function formValues(): array
    {
        return [
            'organization' => $this->organization,
            'position' => $this->position,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_current' => $this->is_current,
            'description' => $this->description,
        ];
    }

    public function periodLabel(): string
    {
        $end = $this->is_current ? 'Present' : ($this->end_date?->format('M Y') ?? 'Present');

        return $this->start_date?->format('M Y').' – '.$end;
    }
}
