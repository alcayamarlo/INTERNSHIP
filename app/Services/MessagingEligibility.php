<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\InternshipApplication;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who may message whom. The single source for MessagePolicy (open thread, send), the contact
 * list, and the send route; checked again at send time so a status change applies at once.
 *
 * - Student ↔ their assigned OJT coordinator: always.
 * - Student ↔ company: only while an application with that company is Reviewed or later
 *   (Interview, Accepted, Completed). Declined follows config('messaging.declined_keeps_chat_open');
 *   Withdrawn always locks. A pending invitation does not count.
 * - Student → admin: only to reply in a thread the admin started. Admins may message anyone.
 * - Students never message other students.
 * - Everyone else (company, coordinator, admin among themselves) is unchanged.
 */
class MessagingEligibility
{
    public const COMPANY_LOCKED_FOR_STUDENT = 'You can message this company once your application is marked Reviewed.';

    public const COMPANY_WITHDRAWN_FOR_STUDENT = 'This chat is closed because your application was withdrawn. You can still read past messages.';

    public const COMPANY_DECLINED_FOR_STUDENT = 'This chat is closed because your application was declined. You can still read past messages.';

    public const STUDENT_LOCKED_FOR_COMPANY = 'You can message this student once their application is Reviewed, or after they accept your invitation.';

    public const COORDINATOR_NOT_ASSIGNED = 'You can only message your assigned OJT coordinator.';

    public const STUDENT_NOT_ASSIGNED = 'You can only message students assigned to you.';

    public const ADMIN_FOR_STUDENT = 'You can reply to an administrator only after they message you.';

    public function canSend(User $sender, User $recipient): Response
    {
        if ($sender->is($recipient)) {
            return Response::deny('You cannot message yourself.');
        }

        $pair = [$sender->role?->value, $recipient->role?->value];

        return match ($pair) {
            [UserRole::Student->value, UserRole::Student->value] => Response::deny('Students cannot message other students.'),
            [UserRole::Student->value, UserRole::Employer->value] => $this->companyChatOpen($recipient, $sender)
                ? Response::allow()
                : Response::deny($this->lockReasonForStudent($recipient, $sender)),
            [UserRole::Employer->value, UserRole::Student->value] => $this->companyChatOpen($sender, $recipient)
                ? Response::allow()
                : Response::deny(self::STUDENT_LOCKED_FOR_COMPANY),
            [UserRole::Student->value, UserRole::Coordinator->value] => $this->isAssignedCoordinator($recipient, $sender)
                ? Response::allow()
                : Response::deny(self::COORDINATOR_NOT_ASSIGNED),
            [UserRole::Coordinator->value, UserRole::Student->value] => $this->isAssignedCoordinator($sender, $recipient)
                ? Response::allow()
                : Response::deny(self::STUDENT_NOT_ASSIGNED),
            [UserRole::Student->value, UserRole::Admin->value] => $this->hasMessageFrom($recipient, $sender)
                ? Response::allow()
                : Response::deny(self::ADMIN_FOR_STUDENT),
            default => Response::allow(),
        };
    }

    /**
     * Opening a thread: anyone who may send, plus either side of a thread that already exists
     * (shown read-only), but never another student's conversation.
     */
    public function canView(User $viewer, User $other): Response
    {
        $send = $this->canSend($viewer, $other);
        if ($send->allowed() || $viewer->is($other)) {
            return $send;
        }

        return $this->hasThread($viewer, $other) ? Response::allow() : $send;
    }

    /** A company and a student can message: some application with this company is Reviewed or later. */
    public function companyChatOpen(User $employerUser, User $studentUser): bool
    {
        return InternshipApplication::query()
            ->whereIn('status', self::openStatusValues())
            ->whereHas('student', fn (Builder $query) => $query->where('user_id', $studentUser->id))
            ->whereHas('internship.employer', fn (Builder $query) => $query->where('user_id', $employerUser->id))
            ->exists();
    }

    /**
     * User ids $user may start a new conversation with, for the "Start new" list.
     */
    public function startableContactIds(User $user): Collection
    {
        $query = User::query()->where('id', '!=', $user->id);

        $ids = match (true) {
            $user->isRole(UserRole::Student) => $this->studentContacts($user, $query),
            $user->isRole(UserRole::Employer) => $query->where(fn (Builder $inner) => $inner
                ->where('role', '!=', UserRole::Student->value)
                ->orWhereIn('id', $this->companyStudentUserIds($user)))->pluck('id'),
            $user->isRole(UserRole::Coordinator) => $query->where(fn (Builder $inner) => $inner
                ->where('role', '!=', UserRole::Student->value)
                ->orWhereIn('id', $user->coordinator
                    ? $user->coordinator->scopedStudentsQuery()->select('user_id')
                    : [0]))->pluck('id'),
            default => $query->pluck('id'),
        };

        return $ids->map(fn ($id) => (int) $id)->values();
    }

    /** @return list<string> */
    public static function openStatusValues(): array
    {
        $statuses = [ApplicationStatus::Reviewed, ApplicationStatus::Interview, ApplicationStatus::Accepted, ApplicationStatus::Completed];
        if (config('messaging.declined_keeps_chat_open')) {
            $statuses[] = ApplicationStatus::Rejected;
        }

        return array_map(fn (ApplicationStatus $status) => $status->value, $statuses);
    }

    /** Why a student cannot message this company right now. */
    public function lockReasonForStudent(User $employerUser, User $studentUser): string
    {
        $latest = InternshipApplication::query()
            ->whereHas('student', fn (Builder $query) => $query->where('user_id', $studentUser->id))
            ->whereHas('internship.employer', fn (Builder $query) => $query->where('user_id', $employerUser->id))
            ->latest('updated_at')
            ->value('status');

        return match ($latest instanceof ApplicationStatus ? $latest : ApplicationStatus::tryFrom((string) $latest)) {
            ApplicationStatus::Withdrawn => self::COMPANY_WITHDRAWN_FOR_STUDENT,
            ApplicationStatus::Rejected => self::COMPANY_DECLINED_FOR_STUDENT,
            default => self::COMPANY_LOCKED_FOR_STUDENT,
        };
    }

    private function studentContacts(User $studentUser, Builder $query): Collection
    {
        $student = $studentUser->student;
        $coordinatorUserIds = $student
            ? Coordinator::query()->get()->filter(fn (Coordinator $coordinator) => $coordinator->canAccessStudent($student))->pluck('user_id')
            : collect();
        $companyUserIds = $student
            ? InternshipApplication::query()
                ->where('student_id', $student->id)
                ->whereIn('status', self::openStatusValues())
                ->with('internship.employer:id,user_id')
                ->get()
                ->pluck('internship.employer.user_id')
            : collect();

        return $query->whereIn('id', $coordinatorUserIds->concat($companyUserIds)->filter()->unique()->all())->pluck('id');
    }

    /** User ids of students with a Reviewed-or-later application to this company. */
    private function companyStudentUserIds(User $employerUser): Collection
    {
        return Student::query()
            ->whereHas('applications', fn (Builder $query) => $query
                ->whereIn('status', self::openStatusValues())
                ->whereHas('internship.employer', fn (Builder $employer) => $employer->where('user_id', $employerUser->id)))
            ->pluck('user_id');
    }

    private function isAssignedCoordinator(User $coordinatorUser, User $studentUser): bool
    {
        $coordinator = $coordinatorUser->coordinator;
        $student = $studentUser->student;

        return $coordinator !== null && $student !== null && $coordinator->canAccessStudent($student);
    }

    private function hasMessageFrom(User $from, User $to): bool
    {
        return Message::query()->where('sender_id', $from->id)->where('receiver_id', $to->id)->exists();
    }

    private function hasThread(User $a, User $b): bool
    {
        return $this->hasMessageFrom($a, $b) || $this->hasMessageFrom($b, $a);
    }
}
