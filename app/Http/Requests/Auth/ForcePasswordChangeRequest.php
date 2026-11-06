<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Field rules for the first-login password change. The current-password check, the lockout, and
 * "must differ from the current password" need the stored hash and the attempt counter, so
 * ForcePasswordChangeController adds those on top.
 */
class ForcePasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', new StrongPassword($this->user()), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Enter your current password (the one you signed in with).',
            'password.required' => 'Enter a new password.',
            'password.confirmed' => 'The new passwords do not match.',
            'password_confirmation.required' => 'Type your new password again to confirm it.',
        ];
    }
}
