<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\PortfolioCategory;
use App\Models\Certificate;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\StudentAward;
use App\Models\User;
use App\Support\PublicFileUrl;
use Illuminate\Support\Collection;

/**
 * The only view of a student that company-facing pages may use (RA 10173).
 *
 * - Sensitive personal fields appear only when the student turned on "Show to companies".
 * - Mobile, email, addresses, references, and documents appear only while the student has an
 *   active (non-withdrawn) application to one of the company's internships.
 * - The emergency contact appears only once the student is placed with the company.
 * - On-request IDs appear only to companies whose applied-to internship asked for them.
 */
class CompanyVisibleProfile
{
    /** Name, photo, and program only: safe for list pages. */
    public function summary(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'name' => $student->user?->name ?? 'Student',
            'photo_url' => PublicFileUrl::relative($student->profile_picture),
            'program' => $student->program,
            'year_level' => $student->year_level,
        ];
    }

    /**
     * Browse Students card. Never contact details, sensitive fields, documents, or ID numbers.
     */
    public function browseCard(Student $student): array
    {
        $profile = $this->browseProfile($student);

        return [
            ...$this->summary($student),
            'skills' => array_slice($profile['skills'], 0, 6),
            'skill_count' => count($profile['skills']),
            'certificates' => array_slice(array_column($profile['certificates'], 'name'), 0, 3),
            'certificate_count' => count($profile['certificates']),
        ];
    }

    /**
     * Browse Students profile: academic background, awards, skills, certificates, and experience only.
     * Contact details stay hidden here even after the student applies; the applicant page shows them.
     */
    public function browseProfile(Student $student): array
    {
        $student->loadMissing(['user', 'major', 'studentSkills.skill', 'experiences', 'competencies.learnedSkills']);

        return [
            ...$this->summary($student),
            'career_objectives' => $student->career_objectives,
            'academic' => array_filter([
                'College' => $student->department,
                'Course' => $student->program,
                'Major' => $student->major?->name,
                'Year level' => $student->year_level,
                'Expected graduation' => $student->expected_graduation?->format('F Y'),
            ], 'filled'),
            'awards' => $this->awards($student),
            'skills' => $student->studentSkills->sortBy('id')->map(fn ($studentSkill) => $studentSkill->skill?->name)->filter()->values()->all(),
            'certificates' => $student->competencies->sortBy('name')->map(fn ($certificate) => [
                'name' => $certificate->name,
                'type' => $certificate->certificate_type?->label(),
                'skill_type' => $certificate->skill_type?->label(),
                'level' => $certificate->proficiency_level?->label(),
                'skills' => $certificate->learnedSkills->pluck('name')->all(),
            ])->values()->all(),
            'experiences' => $student->experiences->sortByDesc('start_date')->map(fn ($experience) => [
                'position' => $experience->position,
                'organization' => $experience->organization,
                'period' => $experience->periodLabel(),
            ])->values()->all(),
        ];
    }

    /** The student has a non-withdrawn application to one of this company's internships (or accepted its invitation). */
    public function hasApplied(Student $student, User $viewer): bool
    {
        return $this->appliedInternships($student->id, $viewer)->isNotEmpty();
    }

    public function for(Student $student, User $viewer, ?InternshipApplication $application = null): array
    {
        $appliedInternships = $this->appliedInternships($student->id, $viewer);
        $hasApplied = $appliedInternships->isNotEmpty();
        $isPlaced = $hasApplied && Placement::query()
            ->where('student_id', $student->id)
            ->where('employer_id', $viewer->employer?->id)
            ->exists();
        $student->loadMissing(['user', 'major', 'studentSkills.skill', 'experiences', 'competencies.learnedSkills']);
        $certifiedNames = $student->certifiedCompetencyNames();

        return [
            ...$this->summary($student),
            'has_applied' => $hasApplied,
            'personal' => $this->personalRows($student),
            'contact' => $hasApplied ? array_filter([
                'Email' => $student->user?->email,
                'Mobile' => $student->user?->phone,
                'City address' => collect([$student->address, $student->city, $student->province, $student->zip_code])->filter()->implode(', '),
                'Provincial address' => $student->provincial_address,
            ], 'filled') : null,
            'emergency_contact' => $isPlaced ? array_filter([
                'Name' => $student->emergency_contact_name,
                'Relationship' => $student->emergency_contact_relationship,
                'Phone' => $student->emergency_contact_phone,
                'Address' => $student->emergency_contact_address,
            ], 'filled') : null,
            'social_links' => array_filter([
                'LinkedIn' => $student->linkedin_url,
                'Facebook' => $student->facebook_url,
                'GitHub' => $student->github_url,
            ], 'filled'),
            'academic' => array_filter([
                'College' => $student->department,
                'Course' => $student->program,
                'Major' => $student->major?->name,
                'Year level' => $student->year_level,
                'Expected graduation' => $student->expected_graduation?->format('F Y'),
                'Elementary' => $this->school($student->elementary_school, $student->elementary_year_graduated),
                'High school / SHS' => $this->school($student->high_school, $student->high_school_year_graduated),
            ], 'filled'),
            'awards' => $this->awards($student),
            'career_objectives' => $student->career_objectives,
            'skills' => $student->studentSkills->sortBy('id')->map(fn ($studentSkill) => $studentSkill->toChip($certifiedNames))
                ->filter(fn (array $skill) => filled($skill['name']))
                ->map(fn (array $skill) => ['name' => $skill['name'], 'level' => $skill['level_label'], 'has_certificate' => $skill['has_certificate']])
                ->values()->all(),
            // Certificates the student added in My Certificates (stored as competencies).
            'competencies' => $student->competencies->sortBy('name')->map(fn ($competency) => [
                'name' => $competency->name,
                'issuer' => $competency->issuing_organization,
                'type' => $competency->certificate_type?->label(),
                'skill_type' => $competency->skill_type?->label(),
                'category' => $competency->certificate_type?->label() ?? $competency->category?->label(),
                'level' => $competency->proficiency_level?->label(),
                'skills' => $competency->learnedSkills->pluck('name')->all(),
            ])->values()->all(),
            'experiences' => $student->experiences->sortByDesc('start_date')->map(fn ($experience) => [
                'position' => $experience->position,
                'organization' => $experience->organization,
                'period' => $experience->periodLabel(),
                'description' => $experience->description,
            ])->values()->all(),
            'projects' => $hasApplied ? $this->projects($student, $application) : [],
            'references' => $hasApplied
                ? $student->characterReferences()->orderBy('id')->get(['name', 'position', 'organization', 'contact_number'])->toArray()
                : [],
            'certificates' => $hasApplied ? $this->certificates($student, $application) : [],
            'documents' => $hasApplied ? $this->documents($student, $appliedInternships, $application) : [],
        ];
    }

    /**
     * Content for the resume PDF (red sidebar layout). The student hands this file to companies, so it
     * follows the same "Show to companies" toggles and never includes ID numbers. Empty rows are dropped.
     * Sidebar: photo/initials, contact, education, skills. Main column: everything else.
     */
    public function resume(Student $student): array
    {
        $student->loadMissing(['user', 'institution', 'studentSkills.skill', 'experiences', 'competencies.learnedSkills', 'certificates', 'characterReferences']);
        $user = $student->user;
        $hours = $student->internshipHours();
        $rows = fn (array $values): array => array_filter($values, 'filled');
        $name = $user?->name ?? trim($student->first_name.' '.$student->last_name);

        return [
            'version' => 3,
            'name' => $name,
            'initials' => collect(preg_split('/\s+/u', trim($name)) ?: [])
                ->filter()
                ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->pipe(fn ($letters) => $letters->count() > 1 ? $letters->first().$letters->last() : $letters->first()) ?? '',
            // The resume photo is only ever the private 2x2 picture, never the profile picture.
            'id_photo_path' => $student->id_photo_path,
            'course' => $student->program,
            'school' => $student->institution?->name,
            'summary' => $student->career_objectives,
            'generated_at' => now('Asia/Manila')->toIso8601String(),
            'contact' => $rows([
                'Mobile' => $user?->phone,
                'Email' => $user?->email,
                'City address' => collect([$student->address, $student->city, $student->province, $student->zip_code])->filter()->implode(', '),
                'Provincial address' => $student->provincial_address,
            ]),
            'personal' => $rows([
                ...$this->personalRows($student),
                'Emergency contact' => $student->emergency_contact_name,
                'Relationship' => $student->emergency_contact_name ? $student->emergency_contact_relationship : null,
                'Emergency phone' => $student->emergency_contact_name ? $student->emergency_contact_phone : null,
            ]),
            'education' => $rows([
                'College' => $student->department,
                'Course' => $student->program,
                'Year level' => $student->year_level,
                'Expected graduation' => $student->expected_graduation?->format('F Y'),
                'Elementary' => $this->school($student->elementary_school, $student->elementary_year_graduated),
                'High school / SHS' => $this->school($student->high_school, $student->high_school_year_graduated),
                'Internship hours' => $hours['required'] ? $hours['rendered'].' / '.$hours['required'] : ($hours['rendered'] ?: null),
            ]),
            'awards' => $this->awards($student),
            'skills' => $student->studentSkills->sortBy('id')->map(fn ($studentSkill) => $studentSkill->skill?->name)->filter()->values()->all(),
            // My Certificates entries first, then older Documents-tab certificates that have no details yet.
            'certificates' => $student->competencies->sortBy('name')->map(fn ($certificate) => [
                'title' => $certificate->name,
                'rows' => $rows([
                    'Type' => $certificate->certificate_type?->label(),
                    'Issuer' => $certificate->issuing_organization,
                    'Level' => $certificate->proficiency_level?->label(),
                    'Skill type' => $certificate->skill_type?->label(),
                    'Date received' => $certificate->obtained_at?->format('M Y'),
                ]),
                'skills' => $certificate->learnedSkills->pluck('name')->all(),
            ])->concat($student->certificates->whereNull('student_competency_id')->map(fn (Certificate $certificate) => [
                'title' => $certificate->title,
                'rows' => $rows([
                    'Issuer' => $certificate->issuer,
                    'Date received' => $certificate->issue_date?->format('M Y'),
                ]),
                'skills' => [],
            ]))->filter(fn (array $certificate) => filled($certificate['title']))->values()->all(),
            'projects' => $student->portfolios()->projects()->latest()->get()->map(fn (Portfolio $project) => [
                'title' => $project->title,
                'rows' => $rows([
                    'Tools used' => $project->tools_used,
                    'Link' => $project->project_url,
                ]),
                'description' => $project->description,
            ])->filter(fn (array $project) => filled($project['title']))->values()->all(),
            'experiences' => $student->experiences->sortByDesc('start_date')->map(fn ($experience) => [
                'title' => $experience->position,
                'rows' => $rows([
                    'Organization' => $experience->organization,
                    'Period' => $experience->periodLabel(),
                ]),
                'description' => $experience->description,
            ])->values()->all(),
            'references' => $student->characterReferences->sortBy('id')->map(fn ($reference) => [
                'title' => $reference->name,
                'rows' => $rows([
                    'Position' => $reference->position,
                    'Company / school' => $reference->organization,
                    'Contact number' => $reference->contact_number,
                ]),
            ])->values()->all(),
        ];
    }

    /**
     * Whether a company user may open a student's requirement document (transcript or requested ID).
     */
    public function canSeeDeploymentDocument(User $viewer, Portfolio $document): bool
    {
        $requirement = $document->deploymentRequirement;
        if (! $requirement?->shared_with_employers || ! $requirement->is_active) {
            return false;
        }

        $internships = $this->appliedInternships((int) $document->student_id, $viewer);

        return $internships->isNotEmpty()
            && (! $requirement->on_request
                || $internships->contains(fn (Internship $internship) => in_array($requirement->code, $internship->requested_documents ?? [], true)));
    }

    /**
     * @return Collection<int, Internship>
     */
    private function appliedInternships(int $studentId, User $viewer): Collection
    {
        $employerId = $viewer->employer?->id;
        if (! $employerId) {
            return collect();
        }

        return Internship::query()
            ->where('employer_id', $employerId)
            ->whereHas('applications', fn ($query) => $query
                ->where('student_id', $studentId)
                ->where('status', '<>', ApplicationStatus::Withdrawn->value))
            ->get(['id', 'title', 'requested_documents', 'requires_character_reference']);
    }

    /** Personal details a company may see; sensitive ones only when the student turned on "Show to companies". */
    private function personalRows(Student $student): array
    {
        return array_filter([
            'Gender' => $student->gender,
            'Date of birth' => $student->date_of_birth?->format('M d, Y'),
            'Place of birth' => $student->place_of_birth,
            'Citizenship' => $student->citizenship,
            'Languages / dialects' => $student->languages,
            'Civil status' => $student->show_civil_status ? $student->civil_status : null,
            'Religion' => $student->show_religion ? $student->religion : null,
            'Height' => $student->show_height && $student->height_cm ? ((float) $student->height_cm).' cm' : null,
            'Weight' => $student->show_weight && $student->weight_kg ? ((float) $student->weight_kg).' kg' : null,
        ], 'filled');
    }

    /**
     * Honors / awards grouped by level, newest year first; empty groups are left out.
     *
     * @return list<array{label: string, awards: list<array{title: string, meta: string}>}>
     */
    private function awards(Student $student): array
    {
        return array_map(fn (array $group) => [
            'label' => $group['label'],
            'awards' => array_map(fn (StudentAward $award) => [
                'title' => $award->title,
                'meta' => $award->metaLine(),
            ], $group['awards']),
        ], StudentAward::grouped($student->awards()->get()));
    }

    private function school(?string $name, ?int $year): ?string
    {
        return $name ? $name.($year ? ' ('.$year.')' : '') : null;
    }

    private function projects(Student $student, ?InternshipApplication $application): array
    {
        return $student->portfolios()->projects()->latest()->get()->map(fn (Portfolio $project) => [
            'title' => $project->title,
            'description' => $project->description,
            'tools_used' => $project->tools_used,
            'project_url' => $project->project_url,
            'achievements' => $project->achievements,
            ...$this->file($project->file_path, $application ? fn () => route('employer.applicants.portfolios.preview', [$application, $project], false) : null),
            'uploaded' => $project->created_at?->format('M d, Y'),
        ])->all();
    }

    private function certificates(Student $student, ?InternshipApplication $application): array
    {
        $allCertificates = $student->certificates()->latest()->get();
        // Old entries the student completed in My Certificates are shown from there instead.
        $certificates = $allCertificates->whereNull('student_competency_id')->values();
        $mirrored = $allCertificates->map(fn (Certificate $certificate) => $certificate->title.'|'.$certificate->file_path)->flip();
        $portfolioCertificates = $student->portfolios()
            ->where('category', PortfolioCategory::Certificate->value)
            ->latest()
            ->get()
            ->reject(fn (Portfolio $file) => $mirrored->has($file->title.'|'.$file->file_path));

        return $certificates->map(fn (Certificate $certificate) => [
            'title' => $certificate->title,
            'issuer' => $certificate->issuer,
            ...$this->file($certificate->file_path, $application ? fn () => route('employer.applicants.certificates.preview', [$application, $certificate], false) : null),
            'uploaded' => $certificate->created_at?->format('M d, Y'),
        ])->concat($portfolioCertificates->map(fn (Portfolio $file) => [
            'title' => $file->title,
            'issuer' => $file->description,
            ...$this->file($file->file_path, $application ? fn () => route('employer.applicants.portfolios.preview', [$application, $file], false) : null),
            'uploaded' => $file->created_at?->format('M d, Y'),
        ]))->values()->all();
    }

    private function documents(Student $student, Collection $appliedInternships, ?InternshipApplication $application): array
    {
        $requestedCodes = $appliedInternships->flatMap(fn (Internship $internship) => $internship->requested_documents ?? [])->unique();

        return $student->deploymentDocuments()
            ->with('deploymentRequirement')
            ->whereNotIn('verification_status', ['rejected'])
            ->orderByDesc('updated_at')
            ->get()
            ->unique('deployment_requirement_id')
            ->filter(fn (Portfolio $document) => $document->deploymentRequirement?->shared_with_employers
                && $document->deploymentRequirement->is_active
                && (! $document->deploymentRequirement->on_request || $requestedCodes->contains($document->deploymentRequirement->code)))
            ->map(fn (Portfolio $document) => [
                'title' => $document->deploymentRequirement->name,
                'issuer' => null,
                ...$this->file($document->file_path, $application ? fn () => route('employer.applicants.portfolios.preview', [$application, $document], false) : null),
                'uploaded' => $document->updated_at?->format('M d, Y'),
            ])
            ->values()
            ->all();
    }

    private function file(?string $path, ?callable $previewUrl): array
    {
        return [
            'has_file' => filled($path),
            'file_extension' => strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)),
            'preview_url' => filled($path) && $previewUrl ? $previewUrl() : null,
        ];
    }
}
