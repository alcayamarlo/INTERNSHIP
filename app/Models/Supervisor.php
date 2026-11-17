<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company's on-site supervisor. The HR account creates it; the supervisor then runs the OJT of the
 * interns assigned to them (Placement::supervisor_user_id): time records, weekly reports, evaluations.
 */
class Supervisor extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'employer_id', 'position', 'created_by_user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Interns assigned to this supervisor. */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'supervisor_user_id', 'user_id');
    }
}
