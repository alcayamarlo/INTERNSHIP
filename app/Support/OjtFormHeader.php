<?php

namespace App\Support;

/**
 * Header for the school's printed OJT forms: school name lines from config/ojt_forms.php and the two
 * seals as data URIs. A seal whose file is missing is null, so the PDF simply leaves that slot out.
 */
final class OjtFormHeader
{
    private const EXTENSIONS = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];

    /** @return array{school_name: string, school_supervision: string, school_address: string, college_name: string, school_seal: ?string, college_seal: ?string} */
    public static function data(): array
    {
        return [
            'school_name' => (string) config('ojt_forms.school_name'),
            'school_supervision' => (string) config('ojt_forms.school_supervision'),
            'school_address' => (string) config('ojt_forms.school_address'),
            'college_name' => (string) config('ojt_forms.college_name'),
            'school_seal' => self::seal((string) config('ojt_forms.school_seal')),
            'college_seal' => self::seal((string) config('ojt_forms.college_seal')),
        ];
    }

    private static function seal(string $basePath): ?string
    {
        if ($basePath === '') {
            return null;
        }

        foreach (self::EXTENSIONS as $extension => $mime) {
            $file = public_path($basePath.'.'.$extension);
            if (is_file($file) && is_readable($file)) {
                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
            }
        }

        return null;
    }
}
