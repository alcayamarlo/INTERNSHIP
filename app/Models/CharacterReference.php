<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterReference extends Model
{
    public const MAX_PER_STUDENT = 3;

    protected $fillable = ['student_id', 'name', 'position', 'organization', 'contact_number'];

    protected function casts(): array
    {
        // The referee's phone number: stored encrypted (App\Support\SensitiveColumns).
        return ['contact_number' => 'encrypted'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
