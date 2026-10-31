<?php

namespace App\Models;

use App\Enums\SkillLevel;
use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A self-declared skill. Never verified and never used for the competency match score.
 */
class StudentSkill extends Model
{
    public const MAX_PER_STUDENT = 20;

    protected $fillable = ['student_id', 'skill_tag_id', 'level'];

    protected function casts(): array
    {
        return ['level' => SkillLevel::class];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(SkillTag::class, 'skill_tag_id');
    }

    /**
     * @param  Collection<int, string>  $certifiedNames  normalized competency names that have a certificate
     */
    public function toChip(?Collection $certifiedNames = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->skill?->name,
            'level' => $this->level?->value,
            'level_label' => $this->level?->label(),
            'has_certificate' => (bool) $certifiedNames?->contains(CatalogName::normalized($this->skill?->name)),
        ];
    }
}
