<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Student photos (profile picture, 2x2). Decoding with GD rejects anything that is not a real
 * JPG/PNG/WebP (e.g. a PHP file renamed to .jpg); the result is re-encoded as JPEG, which drops
 * all EXIF data, including location.
 */
class ImageProcessor
{
    public const MAX_KB = 5120;

    public const ALLOWED_MIMETYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const TYPE_MESSAGE = 'Only JPG, PNG, or WebP images are allowed.';

    public const SIZE_MESSAGE = 'The photo is too large. The limit is 5 MB.';

    public static function rules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'max:'.self::MAX_KB, 'mimetypes:'.implode(',', self::ALLOWED_MIMETYPES)];
    }

    public static function messages(string $field): array
    {
        return [
            "{$field}.required" => 'Choose a photo to upload.',
            "{$field}.file" => self::TYPE_MESSAGE,
            "{$field}.uploaded" => self::SIZE_MESSAGE,
            "{$field}.max" => self::SIZE_MESSAGE,
            "{$field}.mimetypes" => self::TYPE_MESSAGE,
        ];
    }

    /**
     * Saves a square JPEG under a random name and returns its path.
     *
     * @param  array{x: float, y: float, size: float}|null  $crop  Fractions of the upright image: left, top,
     *         and side length relative to its width. Null crops the centre.
     *
     * @throws ValidationException when the file is not a real JPG, PNG, or WebP image
     */
    public function storeSquare(UploadedFile $file, string $field, string $directory, string $disk, int $size, ?array $crop = null): string
    {
        $jpeg = $this->square((string) file_get_contents($file->getRealPath()), $size, $crop, $field);
        $path = trim($directory, '/').'/'.Str::random(40).'.jpg';

        if (! Storage::disk($disk)->put($path, $jpeg)) {
            throw new RuntimeException("Failed to store the photo on the [$disk] disk.");
        }

        return $path;
    }

    /**
     * Upright, square, re-encoded JPEG bytes.
     *
     * @throws ValidationException
     */
    public function square(string $bytes, int $size, ?array $crop = null, string $field = 'photo'): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Photos need the PHP GD extension. Enable extension=gd in php.ini.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $image = in_array($mime, self::ALLOWED_MIMETYPES, true) ? @imagecreatefromstring($bytes) : false;
        if ($image === false) {
            throw ValidationException::withMessages([$field => self::TYPE_MESSAGE]);
        }

        if ($mime === 'image/jpeg') {
            $image = $this->upright($image, $bytes);
        }

        [$x, $y, $side] = $this->cropBox(imagesx($image), imagesy($image), $crop);
        $square = imagecreatetruecolor($size, $size);
        // PNG/WebP transparency becomes white instead of black.
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled($square, $image, 0, 0, $x, $y, $size, $size, $side, $side);

        ob_start();
        imagejpeg($square, null, 86);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($square);

        return $jpeg;
    }

    /** Applies the EXIF orientation that phone cameras record instead of rotating the pixels. */
    private function upright(\GdImage $image, string $bytes): \GdImage
    {
        $exif = false;
        if (function_exists('exif_read_data')) {
            $stream = fopen('php://memory', 'r+b');
            fwrite($stream, $bytes);
            rewind($stream);
            $exif = @exif_read_data($stream);
            fclose($stream);
        }
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        if ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        $degrees = match ($orientation) {
            3 => 180,
            6, 7 => -90,
            5, 8 => 90,
            default => 0,
        };

        return $degrees === 0 ? $image : (imagerotate($image, $degrees, 0) ?: $image);
    }

    /** @return array{0: int, 1: int, 2: int} x, y, side in pixels, kept inside the image */
    private function cropBox(int $width, int $height, ?array $crop): array
    {
        $shortest = min($width, $height);

        if ($crop === null) {
            return [intdiv($width - $shortest, 2), intdiv($height - $shortest, 2), $shortest];
        }

        $side = (int) round(max(0.05, min(1.0, (float) $crop['size'])) * $width);
        $side = max(1, min($side, $shortest));
        $x = (int) round((float) $crop['x'] * $width);
        $y = (int) round((float) $crop['y'] * $height);

        return [max(0, min($x, $width - $side)), max(0, min($y, $height - $side)), $side];
    }
}
