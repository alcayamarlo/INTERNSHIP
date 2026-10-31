<?php

namespace App\Http\Requests\Competency;

use App\Enums\CertificateType;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;
use App\Enums\UserRole;
use App\Models\StudentCompetencySkill;
use App\Services\FileUploadService;
use App\Support\CatalogName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Add a certificate" wizard. Field names keep the original column names
 * (name = Title, proficiency_level = How good are you at it?, evidence = the certificate file).
 */
class StoreCompetencyRequest extends FormRequest
{
    /** Which wizard step owns each field, so the page can jump to the step with an error. */
    public const STEP_FIELDS = [
        1 => ['certificate_type', 'name', 'skill_type', 'proficiency_level'],
        2 => ['skills'],
        3 => ['evidence', 'issuing_organization', 'obtained_at', 'description'],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Student) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $skills = $this->input('skills');
        if (is_array($skills)) {
            $this->merge(['skills' => array_values(array_filter(
                array_map(fn ($skill) => is_string($skill) ? CatalogName::display($skill) : $skill, $skills),
                fn ($skill) => $skill !== ''
            ))]);
        }
    }

    protected function fileIsRequired(): bool
    {
        return true;
    }

    /** Levels a student may pick; existing Expert entries can keep their level when edited. */
    protected function allowedLevels(): array
    {
        return [ProficiencyLevel::Beginner->value, ProficiencyLevel::Intermediate->value, ProficiencyLevel::Advanced->value];
    }

    public function rules(): array
    {
        return [
            'certificate_type' => ['required', Rule::enum(CertificateType::class)],
            'name' => ['required', 'string', 'max:255'],
            'skill_type' => ['required', Rule::enum(SkillType::class)],
            'proficiency_level' => ['required', Rule::in($this->allowedLevels())],
            'skills' => ['required', 'array', 'min:1', 'max:'.StudentCompetencySkill::MAX_PER_CERTIFICATE],
            'skills.*' => ['required', 'string', 'max:60'],
            'evidence' => FileUploadService::profileDocumentRules($this->fileIsRequired()),
            'issuing_organization' => ['nullable', 'string', 'max:255'],
            'obtained_at' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $skills = $this->input('skills');
                if (! is_array($skills)) {
                    return;
                }
                $normalized = array_map(fn ($skill) => CatalogName::normalized((string) $skill), $skills);
                if (count($normalized) !== count(array_unique($normalized))) {
                    $validator->errors()->add('skills', 'You already added this skill.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'certificate_type.required' => 'Please choose Seminar, Workshop, Training, or Certificate.',
            'certificate_type.enum' => 'Please choose Seminar, Workshop, Training, or Certificate.',
            'name.required' => 'Please type the title of your certificate.',
            'skill_type.required' => 'Please choose Technical or Soft Skill.',
            'skill_type.enum' => 'Please choose Technical or Soft Skill.',
            'proficiency_level.required' => 'Please choose Beginner, Intermediate, or Advanced.',
            'proficiency_level.in' => 'Please choose Beginner, Intermediate, or Advanced.',
            'skills.required' => 'Add at least 1 skill you learned.',
            'skills.min' => 'Add at least 1 skill you learned.',
            'skills.max' => 'You can add up to '.StudentCompetencySkill::MAX_PER_CERTIFICATE.' skills per certificate.',
            'skills.*.max' => 'Keep each skill under 60 characters.',
            'evidence.required' => 'Please upload a photo or PDF of your certificate.',
            'evidence.mimes' => 'Only PDF, JPG, or PNG files are allowed.',
            'evidence.mimetypes' => 'Only PDF, JPG, or PNG files are allowed.',
            'evidence.max' => 'The file is too large. The limit is 5 MB.',
            'obtained_at.before_or_equal' => 'The date you received it cannot be in the future.',
            'description.max' => 'Keep the short note under 1000 characters.',
        ];
    }
}
