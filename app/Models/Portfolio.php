<?php

namespace App\Models;

use App\Enums\PortfolioType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'type',
        'description',
        'file_path',
        'verification_status',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PortfolioType::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
