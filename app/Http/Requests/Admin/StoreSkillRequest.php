<?php

namespace App\Http\Requests\Admin;

use App\Models\Skill;
use App\Support\CatalogName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillRequest extends FormRequest
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
        return $this->user()?->can('create', Skill::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_normalized' => ['required', 'string', 'max:255', Rule::unique('skills', 'name_normalized')],
            'category_id' => ['required', 'integer', 'exists:skill_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
