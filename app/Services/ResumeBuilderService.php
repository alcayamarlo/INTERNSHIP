<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
        Storage::disk('public')->put($filename, $pdf->output());

        return Resume::create([
            'student_id' => $student->id,
            'content' => $content,
            'file_path' => $filename,
            'generated_at' => now(),
        ]);
    }
}
