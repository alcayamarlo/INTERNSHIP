<?php

namespace App\Http\Requests\Competency;

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competency = $this->route('competency');

        return $this->user()?->isRole(UserRole::Student)
            && $competency
            && $competency->student_id === $this->user()->student?->id;
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
        return (new StoreCompetencyRequest)->messages();
    }
}
