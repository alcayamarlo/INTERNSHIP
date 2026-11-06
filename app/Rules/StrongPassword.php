<?php

namespace App\Rules;

use App\Models\User;
use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Server-side check of App\Support\PasswordPolicy. Adds one friendly message per broken rule,
 * so the page can list everything to fix at once.
 */
class StrongPassword implements ValidationRule
{
    public function __construct(private ?User $user = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(PasswordPolicy::MESSAGES['length']);

            return;
        }

        foreach (PasswordPolicy::failures($value) as $rule) {
            $fail(PasswordPolicy::MESSAGES[$rule]);
        }
    }
}
