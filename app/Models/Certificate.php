<?php

namespace App\Models;

use App\Enums\PortfolioCategory;
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
        'category',
        'uploaded_by_user_id',
        'expiration_date', 'verification_status', 'review_notes', 'reviewed_at',
        'reviewed_by_user_id',
        'student_competency_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiration_date' => 'date',
            'category' => PortfolioCategory::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** Set once the student completes this old entry in My Certificates. */
    public function completedCertificate(): BelongsTo
    {
        return $this->belongsTo(StudentCompetency::class, 'student_competency_id');
    }
}
