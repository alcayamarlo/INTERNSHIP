<?php

namespace App\Models;

use App\Enums\CertificateType;
use App\Enums\CompetencyCategory;
use App\Enums\SkillType;
use App\Enums\ProficiencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentCompetency extends Model
{
    protected $fillable = [
        'student_id',
        'competency_id',
        'name',
        'category',
        'certificate_type',
        'skill_type',
        'description',
        'proficiency_level',
        'obtained_at',
        'assessment_name', 'issuing_organization', 'evidence_path', 'evidence_name', 'verification_status', 'review_notes', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => CompetencyCategory::class,
            'certificate_type' => CertificateType::class,
            'skill_type' => SkillType::class,
            'proficiency_level' => ProficiencyLevel::class,
            'obtained_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    protected static function booted(): void
    {
        // Older code paths still create rows with only a legacy category; fill the new fields from it,
        // and keep the legacy category in step with the chosen type.
        static::saving(function (StudentCompetency $competency): void {
            if ($competency->certificate_type !== null && $competency->isDirty('certificate_type')) {
                $competency->category = $competency->certificate_type->legacyCategory();
            }
            if ($competency->certificate_type === null || $competency->skill_type === null) {
                [$type, $skillType] = self::fromLegacyCategory($competency->category);
                $competency->certificate_type ??= $type;
                $competency->skill_type ??= $skillType;
            }
        });
    }

    /** @return array{0: CertificateType, 1: SkillType} */
    public static function fromLegacyCategory(?CompetencyCategory $category): array
    {
        return match ($category) {
            CompetencyCategory::Training => [CertificateType::Training, SkillType::Technical],
            CompetencyCategory::Seminar => [CertificateType::Seminar, SkillType::Technical],
            CompetencyCategory::Workshop => [CertificateType::Workshop, SkillType::Technical],
            CompetencyCategory::Soft => [CertificateType::Certificate, SkillType::Soft],
            default => [CertificateType::Certificate, SkillType::Technical],
        };
    }

    public function learnedSkills(): HasMany
    {
        return $this->hasMany(StudentCompetencySkill::class)->orderBy('id');
    }

    /** The old Documents-tab certificate this record was completed from, if any. */
    public function sourceCertificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }
}
