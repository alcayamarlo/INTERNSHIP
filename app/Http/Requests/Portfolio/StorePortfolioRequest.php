<?php

namespace App\Http\Requests\Portfolio;

use App\Enums\PortfolioType;
use App\Enums\UserRole;
use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Student) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PortfolioType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => FileUploadService::documentRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Portfolio title is required.',
            'type.required' => 'Please select a portfolio type.',
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG.',
            'file.max' => 'File size must not exceed 10MB.',
        ];
    }
}
