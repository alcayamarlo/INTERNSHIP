<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A coordinator marked an internship posting as not suitable for their programs; their students don't see it. */
class InternshipFlag extends Model
{
    protected $fillable = ['internship_id', 'coordinator_id', 'reason'];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }
}
