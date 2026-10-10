<?php

namespace App\Http\Requests\Admin;

use App\Models\Skill;
use App\Support\CatalogName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = CatalogName::display($this->input('name'));

        $this->merge([
            'name' => $name,
            'name_normalized' => CatalogName::normalized($name),
        ]);
    }

    public function authorize(): bool
    {
        $skill = $this->route('skill');

        return $skill instanceof Skill && ($this->user()?->can('update', $skill) ?? false);
    }

    public function rules(): array
    {
        $skill = $this->route('skill');

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_normalized' => ['required', 'string', 'max:255', Rule::unique('skills', 'name_normalized')->ignore($skill?->id)],
            'category_id' => ['required', 'integer', 'exists:skill_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
