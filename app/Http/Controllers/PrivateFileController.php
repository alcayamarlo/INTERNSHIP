<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PrivateFileController extends Controller
{
    public function downloadPortfolio(Portfolio $portfolio)
    {
        $this->authorize('download', $portfolio);
        $disk = $this->diskWithFile($portfolio->file_path);

        return Storage::disk($disk)->download(
            $portfolio->file_path,
            $portfolio->title.'.'.pathinfo($portfolio->file_path, PATHINFO_EXTENSION)
        );
    }

    public function downloadCertificate(Certificate $certificate)
    {
        $this->authorize('view', $certificate);

        return Storage::disk($this->diskWithFile($certificate->file_path))->download(
            $certificate->file_path,
            $certificate->title.'.'.pathinfo($certificate->file_path, PATHINFO_EXTENSION)
        );
    }

    public function downloadApplicantPortfolio(InternshipApplication $application, Portfolio $portfolio)
    {
        $this->authorize('view', $application);
        abort_unless($portfolio->student_id === $application->student_id, 404);

        return Storage::disk($this->diskWithFile($portfolio->file_path))->download(
            $portfolio->file_path,
            $portfolio->title.'.'.pathinfo($portfolio->file_path, PATHINFO_EXTENSION)
        );
    }

    public function previewPortfolio(Portfolio $portfolio)
    {
        $this->authorize('preview', $portfolio);
        $disk = $this->diskWithFile($portfolio->file_path);
        $extension = strtolower(pathinfo($portfolio->file_path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        abort_unless(isset($mimeTypes[$extension]), 404);

        return Storage::disk($disk)->response($portfolio->file_path, null, [
            'Content-Type' => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="'.basename($portfolio->title).'.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadResume(Resume $resume)
    {
        $this->authorizeResume($resume);

        return Storage::disk($this->diskWithFile($resume->file_path))->download(
            $resume->file_path,
            'resume-'.$resume->student_id.'.pdf'
        );
    }

    public function viewResume(Resume $resume)
    {
        $this->authorizeResume($resume);

        return Storage::disk($this->diskWithFile($resume->file_path))->response($resume->file_path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadApplicantResume(InternshipApplication $application)
    {
        $this->authorize('downloadResume', $application);
        $resume = $application->student->resumes()->latest()->first();

        abort_unless($resume && $resume->file_path, 404, 'Resume not found.');

        return Storage::disk($this->diskWithFile($resume->file_path))->download($resume->file_path);
    }

    private function authorizeResume(Resume $resume): void
    {
        abort_unless(Auth::user()->student?->id === $resume->student_id, 403);
        $this->diskWithFile($resume->file_path);
    }

    private function diskWithFile(?string $path): string
    {
        abort_unless($path, 404, 'File not found.');

        foreach (['private', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        abort(404, 'File not found.');
    }
}