<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Data Privacy Act of 2012 (RA 10173) consent gate for saving profile data.
 */
class PrivacyConsent
{
    public const STATEMENT = 'I allow SkillBridge to store and share my profile with partner companies for internship purposes (Data Privacy Act of 2012, RA 10173).';

    public const REQUIRED_MESSAGE = 'Please give your privacy consent before saving your profile.';

    public static function isMissing(Request $request, Student $student): bool
    {
        return ! $student->hasPrivacyConsent() && ! $request->boolean('privacy_consent');
    }

    /**
     * Blocks the save until consent is given, recording it when the request carries it.
     */
    public static function ensure(Request $request, Student $student): void
    {
        if (self::isMissing($request, $student)) {
            throw ValidationException::withMessages(['privacy_consent' => self::REQUIRED_MESSAGE]);
        }

        self::record($student);
    }

    public static function record(Student $student): void
    {
        if ($student->hasPrivacyConsent()) {
            return;
        }

        $student->forceFill(['privacy_consent' => true, 'privacy_consent_at' => now()])->save();
    }
}
