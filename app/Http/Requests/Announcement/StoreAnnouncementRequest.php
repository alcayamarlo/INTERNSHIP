<?php

namespace App\Http\Requests\Announcement;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_role' => ['required', Rule::in(['all', ...array_map(
                fn (UserRole $role) => $role->value,
                UserRole::assignable()
            )])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Announcement title is required.',
            'content.required' => 'Announcement content is required.',
            'target_role.required' => 'Please select an announcement audience.',
            'target_role.in' => 'Please select a valid announcement audience.',
        ];
    }
}
