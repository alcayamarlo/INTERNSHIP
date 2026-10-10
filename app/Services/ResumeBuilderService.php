<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResumeBuilderService
{
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

        $pdf = Pdf::loadView('student.resume.pdf', ['content' => $content, 'student' => $student]);
        $filename = 'resumes/student_'.$student->id.'_'.time().'.pdf';

        if (! Storage::disk('private')->put($filename, $pdf->output())) {
            throw new RuntimeException('Unable to store the generated resume.');
        }

        try {
            return DB::transaction(fn () => Resume::create([
                'student_id' => $student->id,
                'content' => $content,
                'file_path' => $filename,
                'generated_at' => now(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($filename);
            throw $exception;
        }
    }
}
