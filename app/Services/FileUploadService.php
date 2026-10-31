<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Support\UploadDisks;
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

    /** Documents-tab uploads (requirements, IDs, certificates): PDF, JPG or PNG up to 5 MB. */
    public const MAX_PROFILE_DOCUMENT_KB = 5120;

    public static function profileDocumentRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:'.self::MAX_PROFILE_DOCUMENT_KB,
        ];
    }

    public static function profileDocumentMessages(string $field = 'file'): array
    {
        return [
            $field.'.required' => 'Choose a file to upload.',
            $field.'.mimes' => 'Upload a PDF, JPG, or PNG file.',
            $field.'.mimetypes' => 'Upload a PDF, JPG, or PNG file.',
            $field.'.max' => 'The file must be 5 MB or smaller.',
        ];
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

    public function upload(UploadedFile $file, string $directory, string $prefix = '', ?string $disk = null): string
    {
        $extension = strtolower($file->extension() ?: 'bin');

        if (! in_array($extension, self::DOCUMENT_EXTENSIONS, true)) {
            throw new FileException('Unsupported file type.');
        }

        return $this->storeFile($file, $directory, $prefix, $extension, $disk ?? UploadDisks::private());
    }

    public function uploadImage(UploadedFile $file, string $directory, string $prefix = '', ?string $disk = null): string
    {
        $extension = strtolower($file->extension() ?: 'bin');

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new FileException('Unsupported image type.');
        }

        return $this->storeFile($file, $directory, $prefix, $extension, $disk ?? UploadDisks::public());
    }

    private function storeFile(UploadedFile $file, string $directory, string $prefix, string $extension, string $disk): string
    {
        $filename = trim($prefix, '_').'_'.now()->timestamp.'_'.Str::random(8).'.'.$extension;
        $path = $file->storeAs(trim($directory, '/'), $filename, $disk);

        if (! $path) {
            throw new \RuntimeException("Failed to store uploaded file on the [$disk] disk.");
        }

        return $path;
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        $disks = $disk
            ? [$disk]
            : (\Illuminate\Support\Str::startsWith($path ?? '', ['profiles/', 'logos/'])
                ? [UploadDisks::public(), 'public']
                : UploadDisks::search());

        foreach ($disks as $storageDisk) {
            if ($path && Storage::disk($storageDisk)->exists($path)) {
                Storage::disk($storageDisk)->delete($path);
            }
        }
    }

    public function deleteIfUnreferenced(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Certificate::where('file_path', $path)->exists()
            || \App\Models\StudentCompetency::where('evidence_path', $path)->exists()
            || Portfolio::where('file_path', $path)->exists()
            || Resume::where('file_path', $path)->exists()) {
            return;
        }

        $this->delete($path);
    }
}
