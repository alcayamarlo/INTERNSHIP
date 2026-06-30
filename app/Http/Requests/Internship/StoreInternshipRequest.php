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
            'requirement_names' => ['nullable', 'array'],
            'requirement_names.*' => ['nullable', 'string', 'max:255'],
            'requirement_levels' => ['nullable', 'array'],
            'requirement_levels.*' => ['nullable', Rule::enum(ProficiencyLevel::class)],
        ];
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
