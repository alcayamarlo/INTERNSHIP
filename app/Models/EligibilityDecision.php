<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A coordinator's approve/reject decision on a student's eligibility. The latest one counts; older ones are history. */
class EligibilityDecision extends Model
{
    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = ['student_id', 'decision', 'reason', 'decided_by_user_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function isRejected(): bool
    {
        return $this->decision === self::REJECTED;
    }
}
