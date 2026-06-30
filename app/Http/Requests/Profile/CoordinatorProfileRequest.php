<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class CoordinatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isRole(\App\Enums\UserRole::Coordinator);
    }

    public function rules(): array
    {
        return [
            // Institution Information
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'office_address' => ['required', 'string', 'max:255'],

            // Personal Information
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . auth()->id()],
            'phone' => ['required', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],

            // Profile Picture
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'department.required' => 'Department is required.',
            'position.required' => 'Position is required.',
            'name.required' => 'Full name is required.',
            'email.unique' => 'This email is already in use.',
            'phone.regex' => 'Please enter a valid phone number.',
            'profile_picture.image' => 'Profile picture must be an image.',
            'profile_picture.mimes' => 'Profile picture must be JPG, JPEG, or PNG.',
            'profile_picture.max' => 'Profile picture must not exceed 2MB.',
        ];
    }
}
