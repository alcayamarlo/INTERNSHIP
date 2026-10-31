<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\CatalogName;
use App\Support\StudentIdFormat;

class Student extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'institution_id',
        'student_id_number',
        'student_id_locked',
        'profile_verified_at',
        'profile_verified_by_user_id',
        'rendered_internship_hours',
        'major_subjects_passed',
        'program_id',
        'major_id',
        'program',
        'year_level',
        'career_objectives',
        'profile_picture',
        'id_photo_path',
        'address',
        'middle_name', 'suffix', 'gender', 'city', 'province', 'zip_code', 'department',
        'first_name', 'last_name',
        'expected_graduation', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location',
        'date_of_birth',
        'section',
        'school_year',
        'semester',
        'enrollment_status',
        'gwa',
        'profile_completion',
        'place_of_birth', 'citizenship', 'languages', 'civil_status', 'religion', 'height_cm', 'weight_kg',
        'show_civil_status', 'show_religion', 'show_height', 'show_weight',
        'provincial_address', 'linkedin_url', 'facebook_url', 'github_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone',
        'elementary_school', 'elementary_year_graduated', 'high_school', 'high_school_year_graduated', 'honors_awards',
        'privacy_consent', 'privacy_consent_at', 'discoverable',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'expected_graduation' => 'date',
            'gwa' => 'decimal:2',
            'profile_verified_at' => 'datetime',
            'rendered_internship_hours' => 'integer',
            'major_subjects_passed' => 'boolean',
            'student_id_locked' => 'boolean',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'show_civil_status' => 'boolean',
            'show_religion' => 'boolean',
            'show_height' => 'boolean',
            'show_weight' => 'boolean',
            'elementary_year_graduated' => 'integer',
            'high_school_year_graduated' => 'integer',
            'privacy_consent' => 'boolean',
            'privacy_consent_at' => 'datetime',
            'discoverable' => 'boolean',
        ];
    }

    public function hasPrivacyConsent(): bool
    {
        return $this->privacy_consent && $this->privacy_consent_at !== null;
    }

    /** Companies may find and invite this student: consent given and "Allow companies to find my profile" on. */
    public function isDiscoverable(): bool
    {
        return $this->hasPrivacyConsent() && $this->discoverable !== false;
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('privacy_consent', true)
            ->whereNotNull('privacy_consent_at')
            ->where('discoverable', true)
            ->whereHas('user', fn (Builder $user) => $user->where('is_active', true)->whereNull('archived_at'));
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(InternshipInvitation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(StudentCompetency::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function deploymentDocuments(): HasMany
    {
        return $this->hasMany(Portfolio::class)->whereNotNull('deployment_requirement_id');
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(StudentChangeRequest::class);
    }

    public function studentSkills(): HasMany
    {
        return $this->hasMany(StudentSkill::class);
    }

    /**
     * Normalized names of this student's competencies that have an uploaded certificate. Read-only:
     * lets a matching skill chip say "Has certificate" without touching the competency itself.
     *
     * @return Collection<int, string>
     */
    public function certifiedCompetencyNames(): Collection
    {
        $certificates = $this->competencies()->whereNotNull('evidence_path')->with('learnedSkills')->get();

        // A profile skill matches a certificate title or any skill learned in a certificate.
        return $certificates->pluck('name')
            ->concat($certificates->flatMap(fn (StudentCompetency $certificate) => $certificate->learnedSkills->pluck('name')))
            ->map(fn (string $name) => CatalogName::normalized($name))
            ->unique()
            ->values();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(StudentExperience::class);
    }

    public function characterReferences(): HasMany
    {
        return $this->hasMany(CharacterReference::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(StudentAward::class);
    }

    /**
     * On-request ID codes asked for by internships this student has an active application to.
     *
     * @return Collection<string, Collection<int, string>> code => internship titles
     */
    public function requestedDocuments(): Collection
    {
        return $this->applications()
            ->where('status', '<>', ApplicationStatus::Withdrawn->value)
            ->with('internship:id,title,requested_documents')
            ->get()
            ->flatMap(fn (InternshipApplication $application) => collect($application->internship?->requested_documents ?? [])
                ->map(fn (string $code) => ['code' => $code, 'internship' => $application->internship->title]))
            ->groupBy('code')
            ->map(fn (Collection $rows) => $rows->pluck('internship')->unique()->values());
    }

    /**
     * Rendered and required OJT hours: from the latest placement's approved logs once placed,
     * otherwise the hours the student entered on the Academic tab.
     *
     * @return array{rendered: int, required: ?int, from_ojt: bool}
     */
    public function internshipHours(): array
    {
        $placement = $this->placements()->withApprovedHours()->latest('id')->first();

        return [
            'rendered' => $placement ? (int) floor($placement->approvedHours()) : (int) $this->rendered_internship_hours,
            'required' => ($placement?->required_hours ?: $this->academicProgram?->required_internship_hours) ?: null,
            'from_ojt' => $placement !== null,
        ];
    }

    public function profileVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_verified_by_user_id');
    }

    public function studentIdNeedsCorrection(?string $pattern = null): bool
    {
        if (blank($this->student_id_number)) {
            return false;
        }

        return ! StudentIdFormat::matches($this->student_id_number, $pattern);
    }

    public const COMPLETION_WEIGHTS = [
        'personal' => 15,
        'contact' => 15,
        'academic' => 15,
        'skills' => 15,
        'portfolio' => 15,
        'references' => 10,
        'documents' => 15,
    ];

    public const MIN_COMPLETION_SKILLS = 3;

    /**
     * Weighted completion per profile tab. Experience is optional and never counted.
     */
    public function profileCompletionSummary(): array
    {
        $user = $this->user;
        $missingFields = fn (array $fields): array => array_keys(array_filter($fields, fn ($value) => blank($value)));

        $missing = [
            'personal' => $missingFields([
                'first name' => $this->first_name ?: str($user?->name)->before(' ')->toString(),
                'last name' => $this->last_name ?: str($user?->name)->afterLast(' ')->toString(),
                'gender' => $this->gender,
                'date of birth' => $this->date_of_birth,
            ]),
            'contact' => $missingFields([
                'mobile number' => $user?->phone,
                'email' => $user?->email,
                'city address' => $this->address && $this->city && $this->province ? $this->address : null,
                'emergency contact name' => $this->emergency_contact_name,
                'emergency contact relationship' => $this->emergency_contact_relationship,
                'emergency contact address' => $this->emergency_contact_address,
                'emergency contact phone' => $this->emergency_contact_phone,
            ]),
            'academic' => $missingFields([
                'college' => $this->department,
                'course' => $this->program,
                'year level' => $this->year_level,
                'expected graduation' => $this->expected_graduation,
                'elementary school' => $this->elementary_school && $this->elementary_year_graduated ? $this->elementary_school : null,
                'high school' => $this->high_school && $this->high_school_year_graduated ? $this->high_school : null,
            ]),
        ];

        $skillCount = $this->studentSkills()->count();
        $missing['skills'] = $skillCount >= self::MIN_COMPLETION_SKILLS
            ? []
            : ['at least '.self::MIN_COMPLETION_SKILLS.' skills ('.$skillCount.' of '.self::MIN_COMPLETION_SKILLS.')'];
        $missing['portfolio'] = $this->portfolios()->projects()->exists() ? [] : ['at least 1 project'];
        $missing['references'] = $this->characterReferences()->exists() ? [] : ['at least 1 character reference'];
        $missing['documents'] = array_values(array_filter([
            $this->resumes()->whereNotNull('file_path')->exists() ? null : 'your resume',
            $this->hasSubmittedRequiredDocument() ? null : 'at least one required document',
        ]));

        $labels = ['personal' => 'Personal', 'contact' => 'Contact', 'academic' => 'Academic', 'skills' => 'Skills', 'portfolio' => 'Portfolio', 'references' => 'Character Reference', 'documents' => 'Documents'];
        $sections = [];
        foreach (self::COMPLETION_WEIGHTS as $key => $weight) {
            $sections[$key] = [
                'label' => $labels[$key],
                'weight' => $weight,
                'complete' => $missing[$key] === [],
                'missing' => $missing[$key],
            ];
        }

        $incomplete = array_filter($sections, fn (array $section) => ! $section['complete']);

        return [
            'percentage' => array_sum(array_map(fn (array $section) => $section['complete'] ? $section['weight'] : 0, $sections)),
            'completed_steps' => count($sections) - count($incomplete),
            'total_steps' => count($sections),
            'remaining_steps' => count($incomplete),
            'missing_steps' => array_values(array_map(
                fn (array $section) => $section['label'].': add '.implode(', ', $section['missing']),
                $incomplete
            )),
            'sections' => $sections,
        ];
    }

    private function hasSubmittedRequiredDocument(): bool
    {
        return $this->deploymentDocuments()
            ->whereIn('verification_status', ['submitted', 'resubmitted', 'pending', 'approved', 'verified'])
            ->whereHas('deploymentRequirement', fn ($query) => $query->required()
                ->where('upload_source', DeploymentRequirement::SOURCE_STUDENT))
            ->exists();
    }

    public function calculateProfileCompletion(): int
    {
        $completion = $this->profileCompletionSummary()['percentage'];
        $this->update(['profile_completion' => $completion]);

        return $completion;
    }

    public function isCoreProfileComplete(): bool
    {
        $fields = [
            $this->student_id_number,
            $this->program,
            $this->year_level,
            $this->career_objectives,
            $this->address,
            $this->date_of_birth,
            $this->profile_picture,
            $this->user?->phone,
        ];

        return collect($fields)->every(fn ($value) => ! empty($value))
            && $this->competencies()->count() >= 3;
    }
}
