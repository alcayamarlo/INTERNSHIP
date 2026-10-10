<?php

namespace App\Http\Requests\Admin;

use App\Models\SkillCategory;
use App\Support\CatalogName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillCategoryRequest extends FormRequest
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
        $category = $this->route('skill_category');

        return $category instanceof SkillCategory && ($this->user()?->can('update', $category) ?? false);
    }

    public function rules(): array
    {
        $category = $this->route('skill_category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_normalized' => ['required', 'string', 'max:255', Rule::unique('skill_categories', 'name_normalized')->ignore($category?->id)],
        ];
    }
}
