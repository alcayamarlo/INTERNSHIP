<?php

namespace App\Http\Requests\Messaging;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('user');
        $sender = $this->user();

        return $recipient instanceof User
            && $sender instanceof User
            && $sender->can('message.send', $recipient);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'submission_id' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Message cannot be empty.',
            'body.max' => 'Message must not exceed 5000 characters.',
        ];
    }

    protected function failedAuthorization(): void
    {
        $recipient = $this->route('user');
        $sender = $this->user();

        if ($recipient instanceof User
            && $sender instanceof User
            && $sender->isRole(UserRole::Student)
            && $recipient->isRole(UserRole::Student)) {
            throw new AuthorizationException('Students cannot message other students.');
        }

        parent::failedAuthorization();
    }
}
