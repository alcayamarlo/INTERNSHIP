<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'issuer',
        'issue_date',
        'file_path',
        'expiration_date', 'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
