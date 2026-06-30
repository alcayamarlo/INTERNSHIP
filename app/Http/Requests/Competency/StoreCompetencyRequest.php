<?php

namespace App\Http\Requests\Competency;

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Student) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(CompetencyCategory::class)],
            'proficiency_level' => ['required', Rule::enum(ProficiencyLevel::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'obtained_at' => ['nullable', 'date', 'before_or_equal:today'],
            'issuing_organization' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Competency name is required.',
            'category.required' => 'Please select a category.',
            'proficiency_level.required' => 'Please select a proficiency level.',
            'obtained_at.before_or_equal' => 'Date obtained cannot be in the future.',
        ];
    }
}
