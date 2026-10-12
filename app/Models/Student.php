<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\StudentIdFormat;

class Student extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'institution_id',
        'student_id_number',
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
        ];
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

    public function profileCompletionSummary(): array
    {
        $fields = [
            ['label' => 'Add your first name', 'value' => $this->first_name ?: str($this->user?->name)->before(' ')->toString()],
            ['label' => 'Add your last name', 'value' => $this->last_name ?: str($this->user?->name)->afterLast(' ')->toString()],
            ['label' => 'Add your email address', 'value' => $this->user?->email],
            ['label' => 'Add your mobile number', 'value' => $this->user?->phone],
            ['label' => 'Add your student ID', 'value' => $this->student_id_number],
            ['label' => 'Select your program', 'value' => $this->program],
            ['label' => 'Add your year level', 'value' => $this->year_level],
            ['label' => 'Add your career objectives', 'value' => $this->career_objectives],
            ['label' => 'Add your address', 'value' => $this->address],
            ['label' => 'Add your city', 'value' => $this->city],
            ['label' => 'Add your province', 'value' => $this->province],
            ['label' => 'Add your ZIP code', 'value' => $this->zip_code],
            ['label' => 'Add your date of birth', 'value' => $this->date_of_birth],
            ['label' => 'Upload a profile photo', 'value' => $this->profile_picture],
        ];

        $completedFields = collect($fields)
            ->filter(fn (array $field) => filled($field['value']))
            ->count();
        $missingSteps = collect($fields)
            ->reject(fn (array $field) => filled($field['value']))
            ->pluck('label')
            ->values();
        $competencyCount = min($this->competencies()->count(), 3);
        for ($competency = $competencyCount + 1; $competency <= 3; $competency++) {
            $missingSteps->push('Add competency ('.$competency.' of 3)');
        }

        $activeRequirements = DeploymentRequirement::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $latestDocuments = $this->deploymentDocuments()
            ->whereIn('deployment_requirement_id', $activeRequirements->modelKeys())
            ->orderByDesc('updated_at')
            ->get()
            ->unique('deployment_requirement_id')
            ->keyBy('deployment_requirement_id');
        $approvedDocumentCount = 0;
        foreach ($activeRequirements as $requirement) {
            $document = $latestDocuments->get($requirement->id);
            $approved = $requirement->upload_source === DeploymentRequirement::SOURCE_SYSTEM
                ? $requirement->code === 'resume' && $this->resumes()->whereNotNull('file_path')->exists()
                : in_array($document?->verification_status, ['approved', 'verified'], true);

            if ($approved) {
                $approvedDocumentCount++;
            } else {
                $missingSteps->push(match ($document?->verification_status) {
                    'submitted', 'pending' => 'Await approval for '.$requirement->name,
                    'rejected' => 'Replace rejected '.$requirement->name,
                    default => $requirement->upload_source === DeploymentRequirement::SOURCE_SYSTEM
                        ? 'Complete '.$requirement->name
                        : 'Upload '.$requirement->name,
                });
            }
        }

        $totalSteps = count($fields) + 3 + $activeRequirements->count();
        $completedSteps = $completedFields + $competencyCount + $approvedDocumentCount;
        $remainingSteps = $totalSteps - $completedSteps;

        return [
            'percentage' => $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 100,
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'remaining_steps' => $remainingSteps,
            'missing_steps' => $missingSteps->all(),
        ];
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
