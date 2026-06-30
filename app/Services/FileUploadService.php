<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploadService
{
    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public const DOCUMENT_MIMETYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    public const IMAGE_MIMETYPES = ['image/jpeg', 'image/png'];

    public const MAX_DOCUMENT_KB = 10240;

    public const MAX_IMAGE_KB = 2048;

    /**
     * Validation rules for document uploads (portfolio, certificates).
     *
     * @return array<int, mixed>
     */
    public static function documentRules(bool $required = true): array
    {
        $rules = [
            'file',
            'mimes:'.implode(',', self::DOCUMENT_EXTENSIONS),
            'mimetypes:'.implode(',', self::DOCUMENT_MIMETYPES),
            'max:'.self::MAX_DOCUMENT_KB,
        ];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    /**
     * Validation rules for image uploads (profile pictures, logos).
     *
     * @return array<int, mixed>
     */
    public static function imageRules(bool $required = false): array
    {
        $rules = [
            'file',
            'image',
            'mimes:jpeg,jpg,png',
            'mimetypes:'.implode(',', self::IMAGE_MIMETYPES),
            'max:'.self::MAX_IMAGE_KB,
        ];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public function upload(UploadedFile $file, string $directory, string $prefix = '', string $disk = 'public'): string
    {
        $extension = strtolower($file->extension() ?: 'bin');

        if (! in_array($extension, self::DOCUMENT_EXTENSIONS, true)) {
            throw new FileException('Unsupported file type.');
        }

        return $this->storeFile($file, $directory, $prefix, $extension, $disk);
    }

    public function uploadImage(UploadedFile $file, string $directory, string $prefix = '', string $disk = 'public'): string
    {
        $extension = strtolower($file->extension() ?: 'bin');

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new FileException('Unsupported image type.');
        }

        return $this->storeFile($file, $directory, $prefix, $extension, $disk);
    }

    private function storeFile(UploadedFile $file, string $directory, string $prefix, string $extension, string $disk): string
    {
        $filename = trim($prefix, '_').'_'.now()->timestamp.'_'.Str::random(8).'.'.$extension;

        return $file->storeAs(trim($directory, '/'), $filename, $disk);
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
