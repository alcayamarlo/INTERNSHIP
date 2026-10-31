<?php

namespace App\Http\Requests\Internship;

use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Enums\WorkSetup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Employer) ?? false;
    }

    public function rules(): array
    {
        return $this->sharedRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:100'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'work_setup' => ['required', Rule::enum(WorkSetup::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:open,closed'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
            'requirement_names' => ['nullable', 'array'],
            'requirement_names.*' => ['nullable', 'string', 'max:255'],
            'requirement_levels' => ['nullable', 'array'],
            'requirement_levels.*' => ['nullable', Rule::enum(ProficiencyLevel::class)],
            'requires_character_reference' => ['nullable', 'boolean'],
            'requested_documents' => ['nullable', 'array'],
            'requested_documents.*' => ['string', Rule::exists('deployment_requirements', 'code')->where('on_request', true)->where('is_active', true)],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        $validated['requires_character_reference'] = $this->boolean('requires_character_reference');
        $validated['requested_documents'] = array_values(array_unique($validated['requested_documents'] ?? []));

        return data_get($validated, $key, $default);
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Internship title is required.',
            'description.required' => 'Internship description is required.',
            'work_setup.required' => 'Please select a work setup.',
        ];
    }
}
