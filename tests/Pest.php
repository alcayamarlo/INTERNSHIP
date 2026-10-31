<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** Fields the student profile form now requires (plus privacy consent); spread before a test's own payload. */
function bioDataDefaults(): array
{
    return [
        'privacy_consent' => '1',
        'gender' => 'Female',
        'date_of_birth' => '2003-05-14',
        'emergency_contact_name' => 'Maria Santos',
        'emergency_contact_relationship' => 'Mother',
        'emergency_contact_address' => '12 Mango Ave, Cebu City',
        'emergency_contact_phone' => '09171234567',
        'department' => 'College of Computer Studies',
        'program' => 'BS Information Technology',
        'year_level' => '3rd Year',
        'expected_graduation' => '2027-05',
        'elementary_school' => 'Cebu Central School',
        'elementary_year_graduated' => 2015,
        'high_school' => 'Cebu City National Science High School',
        'high_school_year_graduated' => 2021,
    ];
}
