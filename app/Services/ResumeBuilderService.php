<?php

namespace App\Services;

use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\UploadDisks;
use RuntimeException;

class ResumeBuilderService
{
    public const MAX_GENERATED_RESUMES = 3;

    public function __construct(
        private CompanyVisibleProfile $companyProfile,
        private ImageProcessor $images,
    ) {}

    public function build(Student $student): Resume
    {
        $content = $this->companyProfile->resume($student);
        $filename = 'resumes/student_'.$student->id.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(8)).'.pdf';

        $disk = UploadDisks::private();
        if (! Storage::disk($disk)->put($filename, $this->render($content, $student))) {
            throw new RuntimeException('Unable to store the generated resume.');
        }

        try {
            $resume = DB::transaction(function () use ($student, $content, $filename) {
                $resume = Resume::create([
                    'student_id' => $student->id,
                    'content' => $content,
                    'file_path' => $filename,
                    'category' => \App\Enums\PortfolioCategory::Resume,
                    'uploaded_by_user_id' => auth()->id(),
                    'generated_at' => now(),
                ]);

                $this->remove($this->resumesFor($student)->slice(self::MAX_GENERATED_RESUMES), $resume);

                return $resume;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($filename);
            throw $exception;
        }

        $student->calculateProfileCompletion();

        return $resume;
    }

    /**
     * Deletes one of the student's resumes. Applications that used it move to their newest
     * remaining resume, so a company reviewing them still has one to open.
     */
    public function delete(Resume $resume): void
    {
        $student = $resume->student;
        $replacement = $this->resumesFor($student)->first(fn (Resume $other) => ! $other->is($resume));

        DB::transaction(fn () => $this->remove(collect([$resume]), $replacement));
        $student->calculateProfileCompletion();
    }

    /**
     * The student's resumes, newest first.
     *
     * @return Collection<int, Resume>
     */
    public function resumesFor(Student $student): Collection
    {
        return Resume::query()
            ->where('student_id', $student->id)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Must run inside a transaction. Rows go first; files are deleted last so a failed delete rolls
     * the rows back. A row whose file was already removed is re-rendered from its content on demand.
     *
     * @param  Collection<int, Resume>  $resumes
     */
    private function remove(Collection $resumes, ?Resume $replacement): void
    {
        if ($resumes->isEmpty()) {
            return;
        }

        $studentId = (int) $resumes->first()->student_id;
        // Only ever this student's own rows.
        $resumes = $resumes->filter(fn (Resume $resume) => (int) $resume->student_id === $studentId);
        $ids = $resumes->pluck('id')->all();

        if ($replacement && (int) $replacement->student_id === $studentId) {
            InternshipApplication::withTrashed()
                ->where('student_id', $studentId)
                ->whereIn('resume_id', $ids)
                ->update(['resume_id' => $replacement->id]);
        }

        Resume::query()->where('student_id', $studentId)->whereKey($ids)->delete();

        foreach ($resumes->pluck('file_path')->filter() as $path) {
            foreach (UploadDisks::search() as $disk) {
                Storage::disk($disk)->delete($path);
                if (Storage::disk($disk)->exists($path)) {
                    throw new RuntimeException('Unable to delete an old resume file.');
                }
            }
        }
    }

    /**
     * Returns the disk holding the resume PDF, re-rendering it from the stored
     * content when the file is gone (e.g. wiped by an ephemeral redeploy).
     */
    public function diskFor(Resume $resume): string
    {
        abort_unless($resume->file_path, 404, 'Resume file not found. Please regenerate it.');

        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($resume->file_path)) {
                return $disk;
            }
        }

        $student = $resume->student;
        abort_unless($student && is_array($resume->content), 404, 'Resume file not found. Please regenerate it.');

        $disk = UploadDisks::private();
        if (! Storage::disk($disk)->put($resume->file_path, $this->render($resume->content, $student))) {
            abort(404, 'Resume file not found. Please regenerate it.');
        }

        return $disk;
    }

    /**
     * The resume PDF as an HTTP response. Everyone gets the stored file, except a company that may
     * not see the student's contact details yet (CompanyVisibleProfile): it gets a copy rendered
     * without them, which is never stored.
     */
    public function pdfResponse(Resume $resume, ?User $viewer, bool $download): Response
    {
        $filename = 'resume-'.$resume->student_id.'.pdf';
        $headers = ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];

        if ($viewer?->isRole(UserRole::Employer) && $resume->student
            && ! $this->companyProfile->hasApplied($resume->student, $viewer) && is_array($resume->content)) {
            return response($this->render($this->withoutContact($resume->content), $resume->student), 200, [
                ...$headers,
                'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            ]);
        }

        $storage = Storage::disk($this->diskFor($resume));

        return $download
            ? $storage->download($resume->file_path, $filename, $headers)
            : $storage->response($resume->file_path, null, [...$headers, 'Content-Disposition' => 'inline']);
    }

    /** Drops mobile, email, and addresses from any content version. */
    private function withoutContact(array $content): array
    {
        $content['contact'] = [];
        $content['personal'] = collect($content['personal'] ?? [])
            ->except(['Mobile', 'Email', 'City address', 'Provincial address', 'email', 'phone', 'address'])
            ->all();

        return $content;
    }

    private function render(array $content, Student $student): string
    {
        $version = (int) ($content['version'] ?? 1);

        // Older snapshots keep the layout they were generated with.
        if ($version < 2) {
            return Pdf::loadView('student.resume.pdf-legacy', ['content' => $content, 'student' => $student])->output();
        }

        if ($version === 2) {
            // Bio-data snapshots predate the 2x2 rule and keep the profile photo they were made with.
            return Pdf::loadView('student.resume.pdf-biodata', [
                'content' => $content,
                'photo' => $this->photoDataUri($content['photo_path'] ?? null, UploadDisks::public()),
            ])->setPaper('a4')->output();
        }

        return Pdf::loadView('student.resume.pdf', [
            'content' => $content,
            'photo' => $this->resumePhoto($content),
        ])->setPaper('a4')->output();
    }

    /**
     * The photo box image, embedded as base64: only the 2x2 picture from the Documents tab, never the
     * profile picture. Null (no 2x2, or the file is missing or unreadable) makes the template show initials.
     */
    public function resumePhoto(array $content): ?string
    {
        return $this->photoDataUri($content['id_photo_path'] ?? null, UploadDisks::private());
    }

    /** Square JPEG data URI (DomPDF has no object-fit, so the crop keeps the photo from stretching). */
    private function photoDataUri(?string $path, string $disk): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            $storage = Storage::disk($disk);
            $bytes = $storage->exists($path) ? $storage->get($path) : null;
            if (! $bytes) {
                return null;
            }

            return 'data:image/jpeg;base64,'.base64_encode($this->images->square($bytes, 400));
        } catch (\Throwable $exception) {
            // Unreadable image, or GD missing: an already-square stored JPEG still works as is.
            $isJpeg = isset($bytes) && $bytes && (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) === 'image/jpeg';

            return $isJpeg && ! $exception instanceof \Illuminate\Validation\ValidationException
                ? 'data:image/jpeg;base64,'.base64_encode($bytes)
                : null;
        }
    }
}
