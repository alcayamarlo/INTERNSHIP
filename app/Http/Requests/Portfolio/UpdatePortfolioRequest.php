<?php

namespace App\Http\Requests\Portfolio;

use App\Enums\PortfolioType;
use App\Enums\UserRole;
use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portfolio = $this->route('portfolio');

        return $this->user()?->isRole(UserRole::Student)
            && $portfolio
            && $portfolio->student_id === $this->user()->student?->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PortfolioType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => FileUploadService::documentRules(required: false),
        ];
    }

    public function messages(): array
    {
        return (new StorePortfolioRequest)->messages();
    }
}
