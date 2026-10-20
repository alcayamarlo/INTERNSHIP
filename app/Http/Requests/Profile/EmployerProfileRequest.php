<?php

namespace App\Http\Requests\Profile;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;

class EmployerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isRole(\App\Enums\UserRole::Employer);
    }

    public function rules(): array
    {
        return [
            // Company Information
            'company_name' => ['required', 'string', 'max:255'],
            'company_logo' => FileUploadService::imageRules(),
            'description' => ['nullable', 'string', 'max:2000'],
            'industry' => ['required', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'in:1-10,11-50,51-200,201-500,500+'],
            'website' => ['nullable', 'url', 'max:255'],

            // Contact Information and Business Address are optional on this
            // form: the company profile page only posts company fields, so
            // they are validated only when sent.
            'contact_person' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . auth()->id()],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],

            'street' => ['sometimes', 'required_with:city,province,zip_code', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required_with:street', 'nullable', 'string', 'max:100'],
            'province' => ['sometimes', 'required_with:street', 'nullable', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'required_with:street', 'nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'contact_person.required' => 'Contact person name is required.',
            'email.unique' => 'This email is already in use.',
            'phone.regex' => 'Please enter a valid phone number.',
            'website.url' => 'Please enter a valid website URL.',
            'company_logo.image' => 'Company logo must be an image.',
            'company_logo.mimes' => 'Logo must be JPEG, PNG, or WebP format.',
            'company_logo.max' => 'Logo must not exceed 2MB.',
        ];
    }
}
