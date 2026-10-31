<?php

namespace App\Http\Requests\Invitation;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Employer) && $this->user()->employer !== null;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'internship_id' => ['required', 'integer', 'exists:internships,id'],
            'message' => ['nullable', 'string', 'max:'.config('invitations.message_max_length', 500)],
        ];
    }

    public function messages(): array
    {
        return [
            'internship_id.required' => 'Choose one of your open internships.',
            'internship_id.exists' => 'Choose one of your open internships.',
            'message.max' => 'Keep the message to 500 characters or fewer.',
        ];
    }
}
