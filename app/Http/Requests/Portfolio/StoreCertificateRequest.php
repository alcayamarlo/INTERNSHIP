<?php

namespace App\Http\Requests\Portfolio;

use App\Enums\UserRole;
use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Student) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file' => FileUploadService::documentRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Certificate title is required.',
            'file.required' => 'Please upload a certificate file.',
            'file.mimes' => 'Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG.',
            'file.max' => 'File size must not exceed 10MB.',
        ];
    }
}
