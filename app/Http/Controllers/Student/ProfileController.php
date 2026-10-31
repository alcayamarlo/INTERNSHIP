<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StudentProfileRequest;
use App\Enums\PortfolioType;
use App\Enums\PortfolioCategory;
use App\Models\Certificate;
use App\Models\DeploymentRequirement;
use App\Enums\AwardLevel;
use App\Enums\SkillLevel;
use App\Models\AcademicSetting;
use App\Models\CharacterReference;
use App\Models\StudentAward;
use App\Models\StudentSkill;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Resume;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\ImageProcessor;
use App\Services\ResumeBuilderService;
use App\Services\AcademicCalendar;
use App\Support\Avatar;
use App\Support\PrivacyConsent;
use App\Support\StudentIdFormat;
use App\View\Presenters\PortfolioItemPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;

class ProfileController extends Controller
{
    /** Profile photos are saved as square JPEGs of this size. */
    public const PROFILE_PHOTO_SIZE = 400;

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
        $internshipHours = $student->internshipHours();
        $requiredInternshipHours = $internshipHours['required'];
        $renderedInternshipHours = $internshipHours['rendered'];
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
        $requestedDocuments = $student->requestedDocuments();
        $deploymentRequirements = DeploymentRequirement::active()
            ->with(['portfolios' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('updated_at')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->reject(fn (DeploymentRequirement $requirement) => $requirement->on_request
                && ! $requestedDocuments->has($requirement->code))
            ->map(function (DeploymentRequirement $requirement) use ($latestResume, $requestedDocuments) {
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
                    'requested_by' => $requestedDocuments->get($requirement->code, collect()),
                ];
            })
            ->values();
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
            'hasPrivacyConsent' => $student->hasPrivacyConsent(),
            'privacyStatement' => PrivacyConsent::STATEMENT,
            'civilStatuses' => StudentProfileRequest::CIVIL_STATUSES,
            'studentSkills' => $student->studentSkills()->with('skill')->orderBy('id')->get(),
            'certifiedSkillNames' => $student->certifiedCompetencyNames(),
            'maxSkills' => StudentSkill::MAX_PER_STUDENT,
            'skillLevels' => SkillLevel::cases(),
            'experiences' => $student->experiences()->orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'projects' => $student->portfolios()->projects()->latest()->get(),
            'references' => $student->characterReferences()->orderBy('id')->get(),
            'maxReferences' => CharacterReference::MAX_PER_STUDENT,
            'awardGroups' => StudentAward::grouped($awards = $student->awards()->get()),
            'awardLevels' => AwardLevel::selectable(),
            'hasOtherAward' => $awards->contains(fn (StudentAward $award) => $award->level === AwardLevel::Other),
            'latestResume' => $latestResume,
            // Certificates now live in My Certificates; the Documents tab keeps resumes and required documents.
            'documentItems' => $portfolioItems
                ->reject(fn (array $item) => $item['type_key'] === PortfolioType::Project->value || $item['is_certificate'])
                ->values(),
            'profileCertificates' => $student->competencies()->with('learnedSkills')->orderBy('name')->get(),
            'certificatesNeedingDetails' => $student->certificates()->whereNull('student_competency_id')->count(),
            'studentIdNeedsCorrection' => $student->studentIdNeedsCorrection(),
            'profileVerified' => $student->profile_verified_at !== null,
            'requiredInternshipHours' => $requiredInternshipHours,
            'renderedInternshipHours' => $renderedInternshipHours,
            'internshipHoursProgress' => $internshipHoursProgress,
            'hoursFromOjt' => $internshipHours['from_ojt'],
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
     * Saves the tab named by profile_section (personal, contact, academic), or all three when it is absent.
     */
    public function update(StudentProfileRequest $request)
    {
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        $user = Auth::user();
        $validated = $request->validated();
        $sections = $request->sections();

        $previousPicture = $student->profile_picture;
        $newPicture = null;

        if ($request->hasFile('profile_picture')) {
            $newPicture = app(ImageProcessor::class)->storeSquare(
                $request->file('profile_picture'),
                'profile_picture',
                'profiles/students',
                UploadDisks::public(),
                self::PROFILE_PHOTO_SIZE
            );
            $validated['profile_picture'] = $newPicture;
        }

        try {
            DB::transaction(function () use ($request, $validated, $sections, $user, $student, $newPicture) {
                PrivacyConsent::record($student);
                $userUpdates = [];
                $studentUpdates = $newPicture ? ['profile_picture' => $newPicture] : [];

                if (in_array('personal', $sections, true)) {
                    $userUpdates['name'] = trim(implode(' ', array_filter([$validated['first_name'], $validated['middle_name'] ?? null, $validated['last_name'], $validated['suffix'] ?? null])));
                    $studentUpdates += $this->personalAttributes($request, $validated);
                }

                if (in_array('contact', $sections, true)) {
                    $userUpdates['email'] = $validated['email'];
                    $userUpdates['phone'] = $validated['phone'];
                    $studentUpdates += collect(self::CONTACT_FIELDS)
                        ->mapWithKeys(fn (string $field) => [$field => $validated[$field] ?? null])
                        ->all();
                }

                if (in_array('academic', $sections, true)) {
                    $studentUpdates += $this->academicAttributes($validated);
                }

                if ($userUpdates !== []) {
                    $user->update($userUpdates);
                }
                $student->update($studentUpdates);

                $student->calculateProfileCompletion();
                $this->activityLog->log($user, 'profile_update', ['role' => 'student']);
            });
        } catch (\Throwable $exception) {
            if ($newPicture) {
                $this->fileUpload->delete($newPicture);
            }

            throw $exception;
        }

        // Only a photo that was actually replaced is deleted; saving a tab without a photo keeps it.
        if ($newPicture && $previousPicture && $previousPicture !== $newPicture) {
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
                ...self::completionPayload($profileCompletion),
                'student_id_number' => $freshStudent->student_id_number,
                'student_id_needs_correction' => $freshStudent->studentIdNeedsCorrection(),
            ]);
        }

        return redirect()->to(route('student.profile.edit').'#'.(count($sections) === 1 ? $sections[0] : 'personal'))
            ->with('success', 'Profile saved');
    }

    public function consent(Request $request)
    {
        $request->validate(['privacy_consent' => ['accepted']], [
            'privacy_consent.accepted' => PrivacyConsent::REQUIRED_MESSAGE,
        ]);
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        PrivacyConsent::record($student);
        $this->activityLog->log(Auth::user(), 'privacy_consent', ['role' => 'student']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Privacy consent recorded.',
                'privacy_consent_at' => $student->fresh()->privacy_consent_at?->format('M d, Y g:i A'),
            ]);
        }

        return back()->with('success', 'Privacy consent recorded.');
    }

    /** "Allow companies to find my profile" (Browse Students and invitations). */
    public function discoverable(Request $request)
    {
        $validated = $request->validate(['discoverable' => ['required', 'boolean']]);
        $student = Auth::user()->student;
        abort_unless($student, 404);

        if ($validated['discoverable'] && ! $student->hasPrivacyConsent()) {
            return redirect()->to(route('student.profile.edit').'#personal')
                ->with('error', 'Agree to the privacy statement first so companies can find your profile.');
        }

        $student->update(['discoverable' => (bool) $validated['discoverable']]);
        $this->activityLog->log(Auth::user(), 'profile_discoverable', ['discoverable' => (bool) $validated['discoverable']]);

        return redirect()->to(route('student.profile.edit').'#personal')->with('success', $validated['discoverable']
            ? 'Companies can now find your profile and invite you to apply.'
            : 'Companies can no longer find your profile or send you new invitations.');
    }

    /**
     * The completion fields every profile save endpoint returns, so the header bar can refresh in place.
     */
    public static function completionPayload(array $profileCompletion): array
    {
        return [
            'profile_completion' => $profileCompletion['percentage'],
            'remaining_steps' => $profileCompletion['remaining_steps'],
            'missing_steps' => $profileCompletion['missing_steps'],
            'completed_sections' => collect($profileCompletion['sections'])
                ->filter(fn (array $section) => $section['complete'])
                ->keys()
                ->values()
                ->all(),
        ];
    }

    private const CONTACT_FIELDS = [
        'address', 'city', 'province', 'zip_code', 'provincial_address',
        'linkedin_url', 'facebook_url', 'github_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone',
    ];

    private function personalAttributes(Request $request, array $validated): array
    {
        return [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'citizenship' => $validated['citizenship'] ?? null,
            'languages' => $validated['languages'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'height_cm' => $validated['height_cm'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'show_civil_status' => $request->boolean('show_civil_status'),
            'show_religion' => $request->boolean('show_religion'),
            'show_height' => $request->boolean('show_height'),
            'show_weight' => $request->boolean('show_weight'),
        ];
    }

    private function academicAttributes(array $validated): array
    {
        $selectedProgram = ! empty($validated['program_id'])
            ? Program::with('coordinator.user')->findOrFail($validated['program_id'])
            : null;

        return [
            'student_id_number' => $validated['student_number'] ?? null,
            'program_id' => $selectedProgram?->id,
            'major_id' => $selectedProgram ? ($validated['major_id'] ?? null) : null,
            'program' => $selectedProgram?->display_name ?? ($validated['program'] ?? null),
            'year_level' => $validated['year_level'],
            'department' => $validated['department'],
            'expected_graduation' => $validated['expected_graduation'],
            'section' => $validated['section'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
            'semester' => $validated['semester'] ?? null,
            'enrollment_status' => $validated['enrollment_status'] ?? null,
            'gwa' => $validated['gwa'] ?? null,
            'elementary_school' => $validated['elementary_school'],
            'elementary_year_graduated' => $validated['elementary_year_graduated'],
            'high_school' => $validated['high_school'],
            'high_school_year_graduated' => $validated['high_school_year_graduated'],
            'career_objectives' => $validated['career_objectives'] ?? null,
            'preferred_internship_field' => $validated['preferred_internship_field'] ?? null,
            'preferred_work_setup' => $validated['preferred_work_setup'] ?? null,
            'preferred_location' => $validated['preferred_location'] ?? null,
        ];
    }

    public function updatePicture(\Illuminate\Http\Request $request, ImageProcessor $images)
    {
        $request->validate(['profile_picture' => ImageProcessor::rules()], ImageProcessor::messages('profile_picture'));
        $student = Auth::user()->student()->firstOrFail();
        $previousPicture = $student->profile_picture;
        $newPicture = $images->storeSquare($request->file('profile_picture'), 'profile_picture', 'profiles/students', UploadDisks::public(), self::PROFILE_PHOTO_SIZE);

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
                'profile_picture_url' => Avatar::url($newPicture),
                'profile_completion' => $student->fresh()->profile_completion,
            ]);
        }

        return back()->with('success', 'Profile picture updated successfully.');
    }

    /**
     * Delete the profile picture ("Remove photo").
     */
    public function deleteProfilePicture(\Illuminate\Http\Request $request)
    {
        $student = Auth::user()->student()->firstOrFail();
        $previousPicture = $student->profile_picture;

        if ($previousPicture) {
            $student->update(['profile_picture' => null]);
            $student->calculateProfileCompletion();
            $this->fileUpload->delete($previousPicture);
        }

        $message = $previousPicture ? 'Profile photo removed.' : 'No profile photo to remove.';
        if ($request->expectsJson()) {
            return response()->json(['success' => (bool) $previousPicture, 'message' => $message, 'initials' => Avatar::initials(Auth::user()->name)]);
        }

        return redirect()->route('student.profile.edit')->with($previousPicture ? 'success' : 'error', $message);
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
