<?php

namespace App\Http\Requests\Admin;

use App\Models\SkillCategory;
use App\Support\CatalogName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillCategoryRequest extends FormRequest
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
        return $this->user()?->can('create', SkillCategory::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_normalized' => ['required', 'string', 'max:255', Rule::unique('skill_categories', 'name_normalized')],
        ];
    }
}
