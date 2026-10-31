<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterReference extends Model
{
    public const MAX_PER_STUDENT = 3;

    protected $fillable = ['student_id', 'name', 'position', 'organization', 'contact_number'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
