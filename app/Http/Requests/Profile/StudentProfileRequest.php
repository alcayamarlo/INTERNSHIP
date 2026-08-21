<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()?->user()?->isRole(\App\Enums\UserRole::Student) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => preg_replace('/[\s().-]+/', '', $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Personal Information
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other,Prefer Not to Say'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],

            // Contact Information
            'email' => ['required', 'email', 'unique:users,email,' . (auth()?->id() ?? 'NULL')],
            'phone' => ['required', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:20'],

            // Academic Information
            'student_number' => ['nullable', 'string', 'max:50'],
            'program' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'year_level' => ['nullable', 'string', 'in:1st Year,2nd Year,3rd Year,4th Year,5th Year'],
            'expected_graduation' => ['nullable', 'date', 'after:date_of_birth'],

            // Career Information
            'career_objectives' => ['nullable', 'string', 'max:1000'],
            'preferred_internship_field' => ['nullable', 'string', 'max:255'],
            'preferred_work_setup' => ['nullable', 'string', 'in:Remote,Hybrid,Onsite'],
            'preferred_location' => ['nullable', 'string', 'max:255'],

            // Profile Picture
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.unique' => 'This email is already in use.',
            'phone.regex' => 'Please enter a valid phone number.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'profile_picture.image' => 'The profile picture must be an image.',
            'profile_picture.mimes' => 'Profile picture must be JPG, JPEG, or PNG.',
            'profile_picture.max' => 'Profile picture must not exceed 2MB.',
        ];
    }
}
