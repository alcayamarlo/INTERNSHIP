<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;
use RuntimeException;

class ResumeBuilderService
{
    public const MAX_GENERATED_RESUMES = 3;

    public function build(Student $student): Resume
    {
        $student->load(['user', 'institution', 'competencies', 'certificates', 'portfolios']);

        $content = [
            'personal' => [
                'name' => $student->user->name,
                'email' => $student->user->email,
                'phone' => $student->user->phone,
                'address' => $student->address,
                'program' => $student->program,
                'year_level' => $student->year_level,
                'institution' => $student->institution?->name,
            ],
            'objectives' => $student->career_objectives,
            'competencies' => $student->competencies->map(fn ($item) => [
                'name' => $item->name,
                'category' => $item->category->label(),
                'level' => $item->proficiency_level->label(),
                'description' => $item->description,
            ])->all(),
            'certificates' => $student->certificates->map(fn ($item) => [
                'title' => $item->title,
                'issuer' => $item->issuer,
                'issue_date' => optional($item->issue_date)->format('M Y'),
            ])->all(),
            'portfolios' => $student->portfolios->map(fn ($item) => [
                'title' => $item->title,
                'type' => $item->type->label(),
                'description' => $item->description,
            ])->all(),
        ];

        $filename = 'resumes/student_'.$student->id.'_'.time().'.pdf';

        $disk = UploadDisks::private();
        if (! Storage::disk($disk)->put($filename, $this->render($content, $student))) {
            throw new RuntimeException('Unable to store the generated resume.');
        }

        try {
            $resume = DB::transaction(fn () => Resume::create([
                'student_id' => $student->id,
                'content' => $content,
                'file_path' => $filename,
                'category' => \App\Enums\PortfolioCategory::Resume,
                'uploaded_by_user_id' => auth()->id(),
                'generated_at' => now(),
            ]));
            $this->pruneOldest($student);
            $student->calculateProfileCompletion();

            return $resume;
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($filename);
            throw $exception;
        }
    }

    /**
     * Permanently deletes the student's oldest generated resumes (archived ones
     * included) so only the most recent MAX_GENERATED_RESUMES remain.
     * Uploaded resumes are left alone.
     */
    private function pruneOldest(Student $student): void
    {
        $stale = Resume::withArchived()
            ->where('student_id', $student->id)
            ->whereNotNull('generated_at')
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get()
            ->slice(self::MAX_GENERATED_RESUMES);

        foreach ($stale as $resume) {
            if ($resume->file_path) {
                foreach (UploadDisks::search() as $disk) {
                    Storage::disk($disk)->delete($resume->file_path);
                }
            }

            $resume->delete();
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

    private function render(array $content, Student $student): string
    {
        return Pdf::loadView('student.resume.pdf', ['content' => $content, 'student' => $student])->output();
    }
}
