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
            'title' => ['sometimes', 'required_without:items', 'string', 'max:255'],
            'type' => ['sometimes', 'required_without:items', Rule::enum(PortfolioType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required_without_all:items,project_url', ...FileUploadService::documentRules(required: false)],
            ...self::projectRules(),
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.type' => ['required_with:items', Rule::enum(PortfolioType::class)],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.file' => ['required_with:items', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function projectRules(): array
    {
        return [
            'tools_used' => ['nullable', 'string', 'max:255'],
            'project_url' => ['nullable', 'url:http,https', 'max:2048'],
            'achievements' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without_all' => 'Upload a screenshot or file, or add a project link.',
            'project_url.url' => 'Enter a full link starting with https://.',
            'title.required' => 'Portfolio title is required.',
            'type.required' => 'Please select a portfolio type.',
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG.',
            'file.max' => 'File size must not exceed 10MB.',
        ];
    }
}
