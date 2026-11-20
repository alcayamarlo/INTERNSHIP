<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    /** SkillBridge serves this one school. There is no way to add, edit or switch institutions. */
    public const NAME = "St. Cecilia's College-Cebu, Inc.";

    /** The school (created, verified, if it is missing). Everything that needs an institution uses this one. */
    public static function primary(): self
    {
        $institution = static::query()->firstOrCreate(['name' => self::NAME], ['verified_at' => now()]);
        if ($institution->verified_at === null) {
            $institution->forceFill(['verified_at' => now()])->save();
        }

        return $institution;
    }

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
