<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Appraisal;
use App\Models\AttendanceLog;
use App\Models\Certificate;
use App\Models\CompanyInvitation;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * The page a notification is about, for the person opening it: clicking a notification goes
 * straight there. Uses the ids saved in the notification's data and checks that the viewer may
 * open that page; null means "show the notification itself" (announcements, deleted items, or
 * a page the viewer may not see).
 */
class NotificationTarget
{
    /** OJT sections; the values are the tab keys of My OJT and the supervisor's intern page. */
    public const TIME_RECORD = 'time-record';

    public const WEEKLY_REPORT = 'weekly-journal';

    public const MONTHLY_EVALUATION = 'monthly-evaluation';

    public const FINAL_EVALUATION = 'evaluation';

    public const SECTIONS = [self::TIME_RECORD, self::WEEKLY_REPORT, self::MONTHLY_EVALUATION, self::FINAL_EVALUATION];

    /** Coordinator placement page tab (URL hash) for each section. */
    private const COORDINATOR_TABS = [
        self::TIME_RECORD => 'time-records',
        self::WEEKLY_REPORT => 'weekly-reports',
        self::MONTHLY_EVALUATION => 'evaluations',
        self::FINAL_EVALUATION => 'evaluations',
    ];

    /** Reminder kinds (OjtDeadlineService) => section. */
    private const REMINDER_SECTIONS = [
        OjtDeadlineService::WEEKLY_REPORT => self::WEEKLY_REPORT,
        OjtDeadlineService::MONTHLY_EVALUATION => self::MONTHLY_EVALUATION,
        OjtDeadlineService::FINAL_EVALUATION => self::FINAL_EVALUATION,
    ];

    public function urlFor(AppNotification $notification, User $viewer): ?string
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return $this->application($data, $viewer)
            ?? $this->studentInvitation($data, $viewer)
            ?? $this->placement($data, $viewer)
            ?? $this->companyInvitation($data, $viewer)
            ?? $this->internship($data, $viewer)
            ?? $this->portfolio($data, $viewer)
            ?? $this->certificate($data, $viewer)
            ?? $this->company($data, $viewer)
            ?? $this->student($data, $viewer)
            ?? $this->conversation($data, $viewer);
    }

    private function id(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    private function application(array $data, User $viewer): ?string
    {
        $application = ($id = $this->id($data, 'application_id'))
            ? InternshipApplication::query()->with(['student', 'internship.employer'])->find($id)
            : null;

        return match (true) {
            $application === null => null,
            $viewer->isRole(UserRole::Student) && $application->student?->user_id === $viewer->id => route('student.applications.show', $application),
            $viewer->isRole(UserRole::Employer) && $application->internship?->employer?->user_id === $viewer->id => route('employer.applicants.show', $application),
            default => null,
        };
    }

    private function studentInvitation(array $data, User $viewer): ?string
    {
        $invitation = ($id = $this->id($data, 'invitation_id')) ? InternshipInvitation::find($id) : null;
        if (! $invitation || ! Gate::forUser($viewer)->allows('view', $invitation)) {
            return null;
        }

        return $viewer->isRole(UserRole::Student) ? route('student.invitations.index') : route('employer.invitations.index');
    }

    /** OJT notifications: the placement page of the viewer's role, opened on the right tab. */
    private function placement(array $data, User $viewer): ?string
    {
        [$placement, $section] = $this->placementAndSection($data);
        if (! $placement || ! Gate::forUser($viewer)->allows('view', $placement)) {
            return null;
        }

        return match ($viewer->role) {
            // Students have no final evaluation tab; its result shows on Progress.
            UserRole::Student => route('student.ojt.index', [
                'placement' => $placement->id,
                'tab' => in_array($section, [self::TIME_RECORD, self::WEEKLY_REPORT, self::MONTHLY_EVALUATION], true) ? $section : 'progress',
            ]).'#ojtDetails',
            UserRole::Employer => route('employer.interns.show', ['placement' => $placement, 'tab' => $section ?? 'progress']),
            UserRole::Supervisor => route('supervisor.interns.show', ['placement' => $placement, 'tab' => $section ?? 'progress']),
            UserRole::Coordinator => route('coordinator.placements.show', $placement).'#'.(self::COORDINATOR_TABS[$section] ?? 'overview'),
            UserRole::Admin => route('admin.placements.show', $placement),
            default => null,
        };
    }

    /** @return array{0: ?Placement, 1: ?string} */
    private function placementAndSection(array $data): array
    {
        $section = in_array($data['section'] ?? null, self::SECTIONS, true) ? $data['section'] : null;
        $section ??= self::REMINDER_SECTIONS[$data['kind'] ?? ''] ?? null;

        $placementId = $this->id($data, 'placement_id');
        foreach ([
            'appraisal_id' => [Appraisal::class, self::MONTHLY_EVALUATION],
            'journal_entry_id' => [JournalEntry::class, self::WEEKLY_REPORT],
            'attendance_log_id' => [AttendanceLog::class, self::TIME_RECORD],
        ] as $key => [$model, $keySection]) {
            if ($id = $this->id($data, $key)) {
                $section ??= $keySection;
                $placementId ??= $model::query()->whereKey($id)->value('placement_id');
            }
        }
        if ($this->id($data, 'evaluation_id')) {
            $section ??= self::FINAL_EVALUATION;
        }

        return [$placementId ? Placement::find($placementId) : null, $section];
    }

    private function companyInvitation(array $data, User $viewer): ?string
    {
        $invitation = ($id = $this->id($data, 'company_invitation_id')) ? CompanyInvitation::find($id) : null;

        return match (true) {
            $invitation === null => null,
            $viewer->isRole(UserRole::Admin) => route('admin.company-invitations.index'),
            $viewer->isRole(UserRole::Coordinator) && Gate::forUser($viewer)->allows('viewAny', CompanyInvitation::class) => route('coordinator.companies.invitations.index'),
            default => null,
        };
    }

    /** Job alerts and deadline reminders link to the posting. */
    private function internship(array $data, User $viewer): ?string
    {
        $internship = ($id = $this->id($data, 'internship_id')) ? Internship::with('employer')->find($id) : null;

        return match (true) {
            $internship === null => null,
            $viewer->isRole(UserRole::Student) => route('student.internships.show', $internship),
            $viewer->isRole(UserRole::Employer) && $internship->employer?->user_id === $viewer->id => route('employer.internships.edit', $internship),
            default => null,
        };
    }

    private function portfolio(array $data, User $viewer): ?string
    {
        $portfolio = ($id = $this->id($data, 'portfolio_id')) ? Portfolio::find($id) : null;

        return $portfolio && $viewer->isRole(UserRole::Student) && Gate::forUser($viewer)->allows('view', $portfolio)
            ? route('student.portfolio.show', $portfolio)
            : null;
    }

    private function certificate(array $data, User $viewer): ?string
    {
        $certificate = ($id = $this->id($data, 'certificate_id')) ? Certificate::find($id) : null;

        return $certificate && $viewer->isRole(UserRole::Student) && Gate::forUser($viewer)->allows('update', $certificate)
            ? route('student.certificates.edit', $certificate)
            : null;
    }

    /** Company notices: the company's own profile, or the partner company page for its coordinator. */
    private function company(array $data, User $viewer): ?string
    {
        if (! ($employerId = $this->id($data, 'employer_id'))) {
            return null;
        }

        return match (true) {
            $viewer->isRole(UserRole::Employer) && $viewer->employer?->id === $employerId => route('employer.profile.edit'),
            $viewer->isRole(UserRole::Coordinator) => route('coordinator.companies.show', $employerId),
            default => null,
        };
    }

    /** Anything else about a student: the coordinator's student record, or the student's own profile. */
    private function student(array $data, User $viewer): ?string
    {
        $student = ($id = $this->id($data, 'student_id')) ? Student::find($id) : null;

        return match (true) {
            $student === null => null,
            $viewer->isRole(UserRole::Coordinator) && (bool) $viewer->coordinator?->canAccessStudent($student) => route('coordinator.students.show', $student),
            $viewer->isRole(UserRole::Student) && $student->user_id === $viewer->id => route('student.profile.edit'),
            default => null,
        };
    }

    private function conversation(array $data, User $viewer): ?string
    {
        $contactId = $this->id($data, 'contact_id') ?? $this->id($data, 'sender_id');
        $contact = $contactId ? User::find($contactId) : null;

        return $contact && Gate::forUser($viewer)->allows('message.send', $contact) ? route('messages.show', $contact) : null;
    }
}
