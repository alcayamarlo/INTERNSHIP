<?php

namespace App\Http\Requests\Messaging;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('user');

        return $recipient instanceof User && $this->canMessage($recipient);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Message cannot be empty.',
            'body.max' => 'Message must not exceed 5000 characters.',
        ];
    }

    private function canMessage(User $recipient): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return match ($user->role) {
            UserRole::Student => $recipient->isRole(UserRole::Employer, UserRole::Coordinator),
            UserRole::Employer, UserRole::Coordinator => $recipient->isRole(UserRole::Student),
            default => false,
        };
    }
}
