<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\InitialPasswordGenerator;
use App\Support\StudentIdFormat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentImportController extends Controller
{
    private const ACCEPTED_DATE_FORMATS = ['Y-m-d', 'm/d/Y'];

    public function __construct(private ActivityLogService $activityLog) {}

    public function create()
    {
        return view('admin.students.import', [
            'institutions' => Institution::orderBy('name')->get(),
        ]);
    }

    public function store(ImportStudentsRequest $request)
    {
        $institutionId = (int) $request->validated('institution_id');
        $rows = $this->readCsv($request->file('file')->getRealPath());
        $results = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // header is row 1
            $results[] = $this->importRow($row, $rowNumber, $institutionId);
        }

        $summary = [
            'created' => collect($results)->where('status', 'created')->count(),
            'updated' => collect($results)->where('status', 'updated')->count(),
            'error' => collect($results)->where('status', 'error')->count(),
        ];

        return view('admin.students.import-results', [
            'results' => $results,
            'summary' => $summary,
        ]);
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }
            $line = array_slice(array_pad($line, count($header), null), 0, count($header));
            $rows[] = array_combine($header, $line);
        }

        fclose($handle);

        return $rows;
    }

    private function importRow(array $row, int $rowNumber, int $institutionId): array
    {
        $schoolId = mb_strtoupper(trim((string) ($row['school_id'] ?? '')));
        $base = ['row' => $rowNumber, 'school_id' => $schoolId];

        $validator = Validator::make($row, [
            'school_id' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'date_of_birth' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return [...$base, 'status' => 'error', 'message' => $validator->errors()->first()];
        }

        if (! StudentIdFormat::matches($schoolId)) {
            return [...$base, 'status' => 'error', 'message' => 'School ID does not match the expected format.'];
        }

        $dateOfBirth = $this->parseDate((string) $row['date_of_birth']);
        if (! $dateOfBirth) {
            return [...$base, 'status' => 'error', 'message' => 'Date of birth could not be read (expected YYYY-MM-DD or MM/DD/YYYY).'];
        }

        $existingStudent = Student::where('student_id_number', $schoolId)->first();

        try {
            if ($existingStudent) {
                DB::transaction(function () use ($row, $existingStudent, $dateOfBirth) {
                    $this->applyRowToStudent($existingStudent, $row, $dateOfBirth);
                    $existingStudent->user->update(['name' => $existingStudent->first_name.' '.$existingStudent->last_name]);
                    $existingStudent->calculateProfileCompletion();

                    $this->activityLog->log(Auth::user(), 'admin_import_student_update', [
                        'user_id' => $existingStudent->user_id,
                        'student_id_number' => $existingStudent->student_id_number,
                    ]);
                });

                return [...$base, 'status' => 'updated', 'message' => 'Existing student record updated.'];
            }

            DB::transaction(function () use ($row, $schoolId, $dateOfBirth, $institutionId) {
                $firstName = trim((string) ($row['first_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));

                $user = User::create([
                    'name' => trim("{$firstName} {$lastName}"),
                    'email' => null,
                    'password' => InitialPasswordGenerator::fromBirthdate($dateOfBirth),
                    'role' => UserRole::Student->value,
                    'must_change_password' => true,
                ]);
                // No email exists to verify for an imported account, so leaving
                // it unverified would permanently lock the student out.
                $user->forceFill(['email_verified_at' => now()])->save();

                $student = Student::create([
                    'user_id' => $user->id,
                    'institution_id' => $institutionId,
                    'student_id_number' => $schoolId,
                    'student_id_locked' => true,
                ]);
                $this->applyRowToStudent($student, $row, $dateOfBirth);
                $student->calculateProfileCompletion();

                $this->activityLog->log(Auth::user(), 'admin_import_student', [
                    'user_id' => $user->id,
                    'student_id_number' => $schoolId,
                ]);
            });
        } catch (\Throwable $e) {
            return [...$base, 'status' => 'error', 'message' => 'Could not create this account: '.$e->getMessage()];
        }

        return [...$base, 'status' => 'created', 'message' => 'Account created.'];
    }

    private function applyRowToStudent(Student $student, array $row, Carbon $dateOfBirth): void
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $middleName = trim((string) ($row['middle_name'] ?? '')) ?: null;
        $programInput = trim((string) ($row['program'] ?? ''));
        $program = $programInput !== ''
            ? Program::where('name', $programInput)->orWhere('code', $programInput)->first()
            : null;

        $student->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'date_of_birth' => $dateOfBirth,
            'program_id' => $program?->id,
            'program' => $program?->display_name ?? ($programInput ?: null),
            'year_level' => trim((string) ($row['year_level'] ?? '')) ?: null,
            'section' => trim((string) ($row['section'] ?? '')) ?: null,
            'school_year' => trim((string) ($row['school_year'] ?? '')) ?: null,
            'semester' => trim((string) ($row['semester'] ?? '')) ?: null,
            'enrollment_status' => 'enrolled',
        ]);
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        foreach (self::ACCEPTED_DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
