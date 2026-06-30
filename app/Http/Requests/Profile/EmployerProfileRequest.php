<?php

namespace App\Http\Requests\Profile;

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
            'company_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
            'industry' => ['required', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'in:1-10,11-50,51-200,201-500,500+'],
            'website' => ['nullable', 'url', 'max:255'],

            // Contact Information
            'contact_person' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . auth()->id()],
            'phone' => ['required', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],

            // Business Address
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:20'],
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
