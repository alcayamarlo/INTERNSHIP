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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\UploadDisks;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ResumeBuilderService
{
    public const MAX_GENERATED_RESUMES = 3;

    /** A resume is one page; two at most when there is a lot to show. */
    public const MAX_PAGES = 2;

    public function __construct(
        private CompanyVisibleProfile $companyProfile,
        private ImageProcessor $images,
    ) {}

    public function build(Student $student): Resume
    {
        return $this->store($student, $this->companyProfile->resume($student));
    }

    /**
     * Saves content as a new resume (newest), then drops the oldest beyond MAX_GENERATED_RESUMES.
     * Used by Generate and by the editor's "Save as new resume".
     */
    public function store(Student $student, array $content): Resume
    {
        $filename = $this->newFilename($student);

        $disk = UploadDisks::private();
        if (! Storage::disk($disk)->put($filename, $this->renderChecked($content, $student))) {
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
     * Replaces a resume's content with edited content and re-renders its PDF, so the stored file
     * (what View, Download, and companies get) matches the editor preview. The new file is written
     * first; the old one is removed only after the row points at the new one.
     */
    public function updateContent(Resume $resume, array $content): Resume
    {
        $student = $resume->student;
        $filename = $this->newFilename($student);
        $disk = UploadDisks::private();
        if (! Storage::disk($disk)->put($filename, $this->renderChecked($content, $student))) {
            throw new RuntimeException('Unable to store the edited resume.');
        }

        $oldPath = $resume->file_path;
        try {
            $resume->update(['content' => $content, 'file_path' => $filename]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($filename);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $filename) {
            foreach (UploadDisks::search() as $searchDisk) {
                Storage::disk($searchDisk)->delete($oldPath);
            }
        }

        return $resume;
    }

    /** PDF bytes for content that may not be saved yet (the editor's Download PDF). */
    public function pdfFor(array $content, Student $student): string
    {
        return $this->renderChecked($content, $student);
    }

    /**
     * How many pages the resume takes, for the editor's page counter. Rendering is the only way to know,
     * so the answer is kept for a few minutes per content (the endpoint is also rate limited).
     *
     * @return array{pages: int, limit: int, fits: bool, density: string}
     */
    public function pageCount(array $content, Student $student): array
    {
        $key = 'resume-pages:'.$student->id.':'.sha1(json_encode(array_diff_key($content, ['edited_at' => 1, 'generated_at' => 1])));

        return Cache::remember($key, now()->addMinutes(10), function () use ($content, $student) {
            $layout = $this->layout($content, $student);

            return ['pages' => $layout['pages'], 'limit' => self::MAX_PAGES, 'fits' => $layout['pages'] <= self::MAX_PAGES, 'density' => $layout['density']];
        });
    }

    /**
     * The resume as HTML for the editor's live preview: the same template as the PDF, so what the
     * student sees is what gets downloaded.
     */
    public function previewHtml(array $content): string
    {
        return view('student.resume.pdf', [
            'content' => $content,
            'photo' => $this->photoFor($content),
            'preview' => true,
        ])->render();
    }

    private function newFilename(Student $student): string
    {
        return 'resumes/student_'.$student->id.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(8)).'.pdf';
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
        // Version 3 and older also carried contact rows inside "personal".
        $content['personal'] = collect($content['personal'] ?? [])
            ->except(['Mobile', 'Email', 'City address', 'Provincial address', 'email', 'phone', 'address'])
            ->all();

        return $content;
    }

    /** The PDF for stored content (a re-render of an old file, or a copy for a company): never refused. */
    private function render(array $content, Student $student): string
    {
        return $this->layout($content, $student)['pdf'];
    }

    /** The PDF of a resume the student is saving or downloading: refused when it cannot fit MAX_PAGES. */
    private function renderChecked(array $content, Student $student): string
    {
        $layout = $this->layout($content, $student);
        if ($layout['pages'] > self::MAX_PAGES) {
            throw ValidationException::withMessages(['resume' => self::tooLong($layout['pages'])]);
        }

        return $layout['pdf'];
    }

    public static function tooLong(int $pages): string
    {
        return "Your resume is {$pages} pages, but a resume can be at most ".self::MAX_PAGES.' pages. Shorten your objective, projects or experience, or remove entries you do not need.';
    }

    /**
     * The PDF and its page count. Older snapshots keep the layout they were generated with (their page
     * count is not tracked). The current layout is tried at normal spacing, then compact spacing, and the
     * best fit is used: one page if either manages it, otherwise two pages at normal spacing, otherwise
     * compact; a result longer than MAX_PAGES is reported so the caller can refuse it.
     *
     * @return array{pdf: string, pages: int, density: string}
     */
    public function layout(array $content, Student $student): array
    {
        $version = (int) ($content['version'] ?? 1);

        if ($version < 2) {
            return ['pdf' => Pdf::loadView('student.resume.pdf-legacy', ['content' => $content, 'student' => $student])->output(), 'pages' => 1, 'density' => 'normal'];
        }

        if ($version === 2) {
            // Bio-data snapshots predate the 2x2 rule and keep the profile photo they were made with.
            return ['pdf' => Pdf::loadView('student.resume.pdf-biodata', [
                'content' => $content,
                'photo' => $this->photoDataUri($content['photo_path'] ?? null, UploadDisks::public()),
            ])->setPaper('a4')->output(), 'pages' => 1, 'density' => 'normal'];
        }

        if ($version === 3) {
            return ['pdf' => Pdf::loadView('student.resume.pdf-v3', [
                'content' => $content,
                'photo' => $this->resumePhoto($content),
            ])->setPaper('a4')->output(), 'pages' => 1, 'density' => 'normal'];
        }

        // Version 4 is the one-column layout; version 5 the burgundy sidebar one.
        $view = $version === 4 ? 'student.resume.pdf-v4' : 'student.resume.pdf';
        $photo = $this->photoFor($content);
        $normal = $this->renderCurrent($view, $content, $photo, 'normal');
        if ($normal['pages'] <= 1) {
            return $normal;
        }
        $compact = $this->renderCurrent($view, $content, $photo, 'compact');
        if ($compact['pages'] <= 1) {
            return $compact;
        }

        return $normal['pages'] <= self::MAX_PAGES ? $normal : $compact;
    }

    /** @return array{pdf: string, pages: int, density: string} */
    private function renderCurrent(string $view, array $content, ?string $photo, string $density): array
    {
        $pdf = Pdf::loadView($view, ['content' => $content, 'photo' => $photo, 'density' => $density])->setPaper('a4');
        $bytes = $pdf->output();

        return ['pdf' => $bytes, 'pages' => (int) $pdf->getDomPDF()->getCanvas()->get_page_count(), 'density' => $density];
    }

    /** The photo for the resume: the private 2x2, unless the student turned it off. */
    private function photoFor(array $content): ?string
    {
        return ($content['show_photo'] ?? true) ? $this->resumePhoto($content) : null;
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
