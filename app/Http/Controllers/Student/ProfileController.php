<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StudentProfileRequest;
use App\Enums\PortfolioType;
use App\Enums\PortfolioCategory;
use App\Models\Certificate;
use App\Models\DeploymentRequirement;
use App\Models\AcademicSetting;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Resume;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\ResumeBuilderService;
use App\Services\AcademicCalendar;
use App\Support\StudentIdFormat;
use App\Support\PublicFileUrl;
use App\View\Presenters\PortfolioItemPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;

class ProfileController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload,
        private ResumeBuilderService $resumeBuilder
    ) {}
    /**
     * Display the student's profile.
     */
    public function edit(Request $request)
    {
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        if ($student->wasRecentlyCreated) {
            $student->calculateProfileCompletion();
        }
        $user = Auth::user();
        $profileCompletion = $student->profileCompletionSummary();
        $programs = Program::with(['majors', 'coordinator.user'])->orderBy('code')->get();
        $academicSettings = AcademicSetting::current();
        $academicCalendar = app(AcademicCalendar::class);
        $academicDefaults = $academicCalendar->defaults(settings: $academicSettings);
        $program = $student->academicProgram;
        $requiredInternshipHours = $program?->required_internship_hours;
        $renderedInternshipHours = (int) $student->rendered_internship_hours;
        $internshipHoursProgress = $requiredInternshipHours
            ? min(100, (int) round(($renderedInternshipHours / $requiredInternshipHours) * 100))
            : 0;
        $selectedProgramId = $student->program_id;
        if (! $selectedProgramId && $student->program) {
            $selectedProgramId = $programs->first(fn (Program $program) => in_array(
                $student->program,
                [$program->name, $program->code, $program->display_name],
                true
            ))?->id;
        }

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $allPortfolios = $student->portfolios()->with('reviewerUser:id,name')->get();
        $allCertificates = $student->certificates()->with('reviewerUser:id,name')->get();
        $allResumes = $student->resumes()
            ->whereNotNull('file_path')
            ->with('reviewerUser:id,name')
            ->orderByDesc('generated_at')
            ->get();
        $latestResume = $allResumes->first();
        $deploymentRequirements = DeploymentRequirement::active()
            ->with(['portfolios' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('updated_at')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (DeploymentRequirement $requirement) use ($latestResume) {
                $document = $requirement->portfolios->first();
                if ($requirement->upload_source === DeploymentRequirement::SOURCE_SYSTEM) {
                    $document = $requirement->code === 'resume' ? $latestResume : null;
                    $status = $document && filled($document->file_path) ? 'Approved' : 'Missing';
                } else {
                    $status = match ($document?->verification_status) {
                        'submitted', 'pending' => 'Submitted',
                        'resubmitted' => 'Resubmitted',
                        'approved', 'verified' => 'Approved',
                        'rejected' => 'Rejected',
                        default => 'Missing',
                    };
                }

                return [
                    'requirement' => $requirement,
                    'document' => $document,
                    'status' => $status,
                ];
            });
        $certificateFiles = $allCertificates
            ->filter(fn (Certificate $certificate) => filled($certificate->file_path))
            ->map(fn (Certificate $certificate) => $certificate->title.'|'.$certificate->file_path)
            ->flip();

        $allPortfolioItems = $allPortfolios
            ->reject(fn (Portfolio $portfolio) => PortfolioItemPresenter::categoryOf($portfolio) === PortfolioCategory::Certificate
                && filled($portfolio->file_path)
                && $certificateFiles->has($portfolio->title.'|'.$portfolio->file_path))
            ->reject(fn (Portfolio $portfolio) => PortfolioItemPresenter::typeOf($portfolio)?->value === PortfolioType::Certificate->value
                && filled($portfolio->file_path)
                && $certificateFiles->has($portfolio->title.'|'.$portfolio->file_path))
            ->concat($allCertificates)
            ->concat($allResumes)
            ->each(function (Portfolio|Certificate|Resume $item): void {
                if ($item instanceof Resume) {
                    $item->setAttribute('title', 'Resume · '.$item->generated_at?->format('M d, Y'));
                }
            })
            ->sortByDesc('created_at')
            ->values();

        $portfolioItems = $allPortfolioItems
            ->map(function (Portfolio|Certificate|Resume $item): array {
                $item->setAttribute('file_size_bytes', $this->fileSize($item->file_path));

                return PortfolioItemPresenter::present($item);
            })
            ->filter(function (array $item) use ($search, $type): bool {
                if ($search !== '' && ! str_contains($item['search'], mb_strtolower($search))) {
                    return false;
                }

                if ($type === 'documents') {
                    return ! in_array($item['type_key'], [PortfolioType::Certificate->value, PortfolioType::Project->value], true);
                }

                return $type === '' || $item['type_key'] === $type;
            })
            ->values();

        return view('student.profile.edit', [
            'student' => $student,
            'profileCompletion' => $profileCompletion,
            'studentIdNeedsCorrection' => $student->studentIdNeedsCorrection(),
            'profileVerified' => $student->profile_verified_at !== null,
            'requiredInternshipHours' => $requiredInternshipHours,
            'renderedInternshipHours' => $renderedInternshipHours,
            'internshipHoursProgress' => $internshipHoursProgress,
            'studentChangeRequests' => $student->changeRequests()->latest()->get(),
            'user' => $user,
            'programs' => $programs,
            'selectedProgramId' => $selectedProgramId,
            'academicSettings' => $academicSettings,
            'studentIdPattern' => StudentIdFormat::pattern(),
            'academicYears' => $academicCalendar->schoolYears(settings: $academicSettings),
            'academicDefaults' => $academicDefaults,
            'semesterOptions' => $academicCalendar->termOptions($academicSettings),
            'monthOptions' => collect(range(1, 12))
                ->mapWithKeys(fn (int $month) => [$month => \Carbon\CarbonImmutable::create(2024, $month, 1)->format('F')]),
            'careerInfo' => $student->only(['preferred_internship_field', 'preferred_work_setup', 'preferred_location', 'expected_graduation']),
            'portfolioItems' => $portfolioItems,
            'deploymentRequirements' => $deploymentRequirements,
            'portfolioTypes' => PortfolioType::cases(),
            'portfolioFilters' => ['search' => $search, 'type' => $type],
            'portfolioStats' => [
                'total' => $allPortfolioItems->count(),
                'certificates' => $allPortfolioItems->filter(function (Portfolio|Certificate|Resume $item): bool {
                    $category = PortfolioItemPresenter::categoryOf($item);

                    return $item instanceof Certificate
                        || $category === PortfolioCategory::Certificate
                        || ($category === null && PortfolioItemPresenter::typeOf($item) === PortfolioType::Certificate);
                })->count(),
                'projects' => $allPortfolioItems->filter(function (Portfolio|Certificate|Resume $item): bool {
                    $category = PortfolioItemPresenter::categoryOf($item);

                    return $category === PortfolioCategory::Project
                        || ($category === null && PortfolioItemPresenter::typeOf($item) === PortfolioType::Project);
                })->count(),
                'storage_usage' => $this->calculateStorageUsage($student),
            ],
        ]);
    }

    /**
     * Update the student's profile.
     * Handles personal, contact, academic, career, and profile picture updates.
     */
    public function update(StudentProfileRequest $request)
    {
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        $user = Auth::user();
        $validated = $request->validated();
        $selectedProgram = ! empty($validated['program_id'])
            ? Program::with('coordinator.user')->findOrFail($validated['program_id'])
            : null;
        $selectedProgramName = $selectedProgram?->display_name ?? ($validated['program'] ?? null);

        $previousPicture = $student->profile_picture;
        $newPicture = null;

        if ($request->hasFile('profile_picture')) {
            $newPicture = $this->fileUpload->uploadImage(
                $request->file('profile_picture'),
                'profiles/students',
                (string) $student->id
            );
            $validated['profile_picture'] = $newPicture;
        }

        try {
            DB::transaction(function () use ($validated, $user, $student, $newPicture, $selectedProgram, $selectedProgramName) {
                $user->update([
                    'name' => trim(implode(' ', array_filter([$validated['first_name'], $validated['middle_name'] ?? null, $validated['last_name'], $validated['suffix'] ?? null]))),
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ]);

                $student->update([
                    'profile_picture' => $newPicture ?? $student->profile_picture,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'address' => $validated['address'],
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'student_id_number' => $validated['student_number'] ?? null,
                    'program_id' => $selectedProgram?->id,
                    'major_id' => $selectedProgram ? ($validated['major_id'] ?? null) : null,
                    'program' => $selectedProgramName,
                    'year_level' => $validated['year_level'] ?? null,
                    'career_objectives' => $validated['career_objectives'] ?? null,
                    'middle_name' => $validated['middle_name'] ?? null,
                    'suffix' => $validated['suffix'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'province' => $validated['province'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'expected_graduation' => $validated['expected_graduation'] ?? null,
                    'section' => $validated['section'] ?? null,
                    'school_year' => $validated['school_year'] ?? null,
                    'semester' => $validated['semester'] ?? null,
                    'enrollment_status' => $validated['enrollment_status'] ?? null,
                    'gwa' => $validated['gwa'] ?? null,
                    'preferred_internship_field' => $validated['preferred_internship_field'] ?? null,
                    'preferred_work_setup' => $validated['preferred_work_setup'] ?? null,
                    'preferred_location' => $validated['preferred_location'] ?? null,
                ]);

                $student->calculateProfileCompletion();
                $this->activityLog->log($user, 'profile_update', ['role' => 'student']);
            });
        } catch (\Throwable $exception) {
            if ($newPicture) {
                $this->fileUpload->delete($newPicture);
            }

            throw $exception;
        }

        if ($previousPicture && $previousPicture !== $newPicture) {
            $this->fileUpload->delete($previousPicture);
        }

        $freshStudent = $student->fresh();

        if ($freshStudent->isCoreProfileComplete() && ! $student->resumes()->whereNotNull('file_path')->exists()) {
            try {
                $this->resumeBuilder->build($freshStudent);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
        $freshStudent = $student->fresh();
        $profileCompletion = $freshStudent->profileCompletionSummary();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile saved',
                'profile_completion' => $profileCompletion['percentage'],
                'remaining_steps' => $profileCompletion['remaining_steps'],
                'missing_steps' => $profileCompletion['missing_steps'],
                'student_id_number' => $freshStudent->student_id_number,
                'student_id_needs_correction' => $freshStudent->studentIdNeedsCorrection(),
            ]);
        }

        return redirect()->route('student.profile.edit')
            ->with('success', 'Profile saved');
    }

    public function updatePicture(\Illuminate\Http\Request $request)
    {
        $request->validate(['profile_picture' => FileUploadService::imageRules(true)]);
        $student = Auth::user()->student()->firstOrFail();
        $previousPicture = $student->profile_picture;
        $newPicture = $this->fileUpload->uploadImage($request->file('profile_picture'), 'profiles/students', (string) $student->id);

        try {
            DB::transaction(function () use ($student, $newPicture, $previousPicture) {
                $student->update(['profile_picture' => $newPicture]);
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($newPicture);
            throw $exception;
        }
        $student->calculateProfileCompletion();

        if ($previousPicture) {
            $this->fileUpload->delete($previousPicture);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'profile_picture_url' => PublicFileUrl::relative($newPicture),
                'profile_completion' => $student->fresh()->profile_completion,
            ]);
        }

        return back()->with('success', 'Profile picture updated successfully.');
    }

    /**
     * Delete the profile picture.
     */
    public function deleteProfilePicture()
    {
        $student = Auth::user()->student()->firstOrFail();

        if ($student->profile_picture) {
            $previousPicture = $student->profile_picture;
            DB::transaction(function () use ($student, $previousPicture) {
                $student->update(['profile_picture' => null]);
            });
            $student->calculateProfileCompletion();
            $this->fileUpload->delete($previousPicture);

            return redirect()->route('student.profile.edit')
                ->with('success', 'Profile picture removed successfully!');
        }

        return redirect()->route('student.profile.edit')
            ->with('error', 'No profile picture to delete.');
    }

    private function calculateStorageUsage(Student $student): string
    {
        $paths = $student->portfolios()->whereNotNull('file_path')->pluck('file_path')
            ->merge($student->certificates()->whereNotNull('file_path')->pluck('file_path'))
            ->merge($student->resumes()->whereNotNull('file_path')->pluck('file_path'))
            ->filter()
            ->unique();
        $totalBytes = $paths->sum(function ($path) {
            foreach (UploadDisks::search() as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->size($path);
                }
            }

            return 0;
        });

        return number_format($totalBytes / (1024 * 1024), 2).' MB';
    }

    private function fileSize(?string $path): int
    {
        if (! $path) {
            return 0;
        }

        foreach (UploadDisks::search() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->size($path);
            }
        }

        return 0;
    }
}
