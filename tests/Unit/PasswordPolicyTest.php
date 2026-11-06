<?php

use App\Support\PasswordPolicy;

// The details of a sample student: Juan Carlos Dela Cruz, born May 15, 2008, school ID SCC-22-00012345.
function samplePersonal(): array
{
    return [
        'birthday' => ['05152008', '15052008', '20080515', '051508', '150508', '080515', '5152008', '1552008', '0515', '1505', '2008'],
        'name' => ['juan', 'carlos', 'dela', 'cruz'],
        'identity' => ['juandc', 'scc2200012345', '2200012345', '00012345'],
    ];
}

it('checks passwords against every rule', function (string $password, array $expectedFailures) {
    expect(PasswordPolicy::failures($password, samplePersonal()))->toBe($expectedFailures);
})->with([
    // Valid
    '1. valid: mixed, 16 characters' => ['Mango!Tree#Sky27', []],
    '2. valid: long passphrase with symbols' => ['Brisk-Harbor_Lantern42?', []],
    // Invalid
    '3. too short' => ['Mn7!x', ['length']],
    '4. missing uppercase, number, and symbol; has a space' => ['mango tree sky river', ['uppercase', 'number', 'special', 'spaces']],
    '5. contains the birthday (MMDD)' => ['Mango!Tree#0515z', ['birthday']],
    '6. contains the last name' => ['Cruz!Mango#Tree7', ['name']],
    '7. common password' => ['Password123!', ['common']],
    '8. keyboard pattern and number run' => ['Qwerty12345!', ['common', 'pattern']],
]);

it('catches the birthday written with separators and the ID number', function () {
    expect(PasswordPolicy::failures('Mango!05/15/2008z', samplePersonal()))->toContain('birthday')
        ->and(PasswordPolicy::failures('Mango!Z#00012345', samplePersonal()))->toContain('identity');
});

it('catches repeated characters and look-alike swaps', function () {
    expect(PasswordPolicy::failures('Mango!Treeee#Sky7', samplePersonal()))->toBe(['pattern'])
        ->and(PasswordPolicy::failures('P@ssw0rd!Mango7', samplePersonal()))->toBe(['common']);
});

it('rejects passwords longer than 64 characters', function () {
    expect(PasswordPolicy::failures('Mango!Tree#Sky27'.str_repeat('Xy9!', 13), samplePersonal()))->toBe(['length']);
});
