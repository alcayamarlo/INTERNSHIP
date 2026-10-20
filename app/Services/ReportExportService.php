<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function generate(User $user, string $type, string $format = 'pdf', ?callable $afterCreate = null): Report
    {
        $data = match ($type) {
            'placement' => $this->placementData(),
            'student' => $this->studentData(),
            'competency' => $this->competencyData(),
            'employer' => $this->employerData(),
            'internship' => $this->internshipData(),
            'ojt' => $this->ojtData(),
            default => throw new InvalidArgumentException("Unsupported report type: {$type}."),
        };

        $filename = 'reports/'.$type.'_'.now()->format('Ymd_His').'.'.$format;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', ['type' => $type, 'data' => $data, 'title' => ucfirst($type).' Report']);
            $stored = Storage::disk(UploadDisks::private())->put($filename, $pdf->output());
        } else {
            $stored = Storage::disk(UploadDisks::private())->put($filename, $this->toCsv($data));
        }

        if (! $stored) {
            throw new RuntimeException("Unable to generate report {$type} in {$format} format.");
        }

        try {
            return DB::transaction(function () use ($user, $type, $format, $filename, $afterCreate) {
                $report = Report::create([
                    'generated_by' => $user->id,
                    'type' => $type,
                    'parameters' => ['generated_at' => now()->toDateTimeString()],
                    'file_path' => $filename,
                    'format' => $format,
                ]);

                if ($afterCreate) {
                    $afterCreate($report);
                }

                return $report;
            });
        } catch (\Throwable $exception) {
            Storage::disk(UploadDisks::private())->delete($filename);
            throw $exception;
        }
    }

    public function download(Report $report): StreamedResponse
    {
        $mime = $report->format === 'pdf' ? 'application/pdf' : 'text/csv';
        $disk = $report->file_path && Storage::disk(UploadDisks::private())->exists($report->file_path)
            ? UploadDisks::private()
            : 'public';

        abort_unless($report->file_path && Storage::disk($disk)->exists($report->file_path), 404, 'Report file not found.');

        return Response::streamDownload(function () use ($report, $disk) {
            echo Storage::disk($disk)->get($report->file_path);
        }, basename($report->file_path), ['Content-Type' => $mime]);
    }

    private function placementData(): array
    {
        return InternshipApplication::with(['student.user', 'internship.employer'])
            ->latest()
            ->get()
            ->map(fn ($app) => [
                'student' => $app->student->user->name,
                'company' => $app->internship->employer->company_name,
                'internship' => $app->internship->title,
                'status' => $app->status->label(),
                'match' => $app->match_percentage.'%',
            ])
            ->all();
    }

    private function studentData(): array
    {
        return Student::with(['user', 'institution'])
            ->get()
            ->map(fn ($student) => [
                'name' => $student->user->name,
                'email' => $student->user->email,
                'program' => $student->program,
                'year_level' => $student->year_level,
                'institution' => $student->institution?->name,
                'profile_completion' => $student->profile_completion.'%',
            ])
            ->all();
    }

    private function competencyData(): array
    {
        return Student::with(['user', 'competencies'])
            ->get()
            ->flatMap(fn ($student) => $student->competencies->map(fn ($comp) => [
                'student' => $student->user->name,
                'competency' => $comp->name,
                'category' => $comp->category->label(),
                'level' => $comp->proficiency_level->label(),
            ]))
            ->all();
    }

    private function employerData(): array
    {
        return Employer::with('user')
            ->get()
            ->map(fn ($employer) => [
                'company' => $employer->company_name,
                'industry' => $employer->industry,
                'contact' => $employer->user->email,
                'internships' => $employer->internships()->count(),
            ])
            ->all();
    }

    private function internshipData(): array
    {
        return Internship::with('employer')
            ->get()
            ->map(fn ($internship) => [
                'title' => $internship->title,
                'company' => $internship->employer->company_name,
                'location' => $internship->location,
                'status' => $internship->status,
                'applications' => $internship->applications()->count(),
            ])
            ->all();
    }

    private function ojtData(): array
    {
        return Placement::with(['student.user', 'employer', 'internship', 'evaluation'])
            ->withApprovedHours()
            ->latest()
            ->get()
            ->map(fn (Placement $placement) => [
                'student' => $placement->student?->user?->name,
                'company' => $placement->employer?->company_name,
                'internship' => $placement->internship?->title,
                'start_date' => $placement->start_date?->toDateString(),
                'hours' => number_format($placement->approvedHours(), 2).' / '.$placement->required_hours,
                'evaluation' => $placement->evaluation ? number_format((float) $placement->evaluation->average_score, 2) : '—',
                'status' => $placement->statusLabel(),
            ])
            ->all();
    }

    private function toCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
