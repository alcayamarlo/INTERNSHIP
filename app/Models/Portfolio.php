<?php

namespace App\Models;

use App\Enums\PortfolioType;
use App\Enums\PortfolioCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'type',
        'category',
        'deployment_requirement_id',
        'description',
        'file_path',
        'verification_status',
        'review_notes',
        'reviewed_at',
        'uploaded_by_user_id',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PortfolioType::class,
            'category' => PortfolioCategory::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function deploymentRequirement(): BelongsTo
    {
        return $this->belongsTo(DeploymentRequirement::class);
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
