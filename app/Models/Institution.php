<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'name',
        'address',
        'contact_email',
        'contact_phone',
        'description',
        'verified_at',
        'verified_by_user_id',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function coordinators(): HasMany
    {
        return $this->hasMany(Coordinator::class);
    }
}
