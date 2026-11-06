<?php

use App\Support\PasswordPolicy;

it('checks passwords against every rule', function (string $password, array $expectedFailures) {
    expect(PasswordPolicy::failures($password))->toBe($expectedFailures);
})->with([
    // Valid
    '1. valid: mixed, 16 characters' => ['Mango!Tree#Sky27', []],
    '2. valid: long passphrase with symbols' => ['Brisk-Harbor_Lantern42?', []],
    // Invalid
    '3. too short' => ['Mn7!x', ['length']],
    '4. missing uppercase, number, and symbol' => ['mangotreeskyriver', ['uppercase', 'number', 'special']],
    '5. missing lowercase' => ['MANGO!TREE#SKY27', ['lowercase']],
]);

it('rejects passwords longer than 64 characters', function () {
    expect(PasswordPolicy::failures('Mango!Tree#Sky27'.str_repeat('Xy9!', 13)))->toBe(['length']);
});
