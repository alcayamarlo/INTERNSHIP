<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipInvitation;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public const ACCEPTED_MESSAGE = 'You accepted the invitation. The company will contact you about the interview. You can now message the company.';

    public function __construct(
        private ApplicationService $applications,
        private InternshipEligibility $eligibility,
        private NotificationService $notifications,
        private ActivityLogService $activityLog,
    ) {}

    /**
     * @throws ValidationException
     */
    public function invite(User $employerUser, Student $student, Internship $internship, ?string $message): InternshipInvitation
    {
        $employer = $employerUser->employer;

        if (! $student->isDiscoverable() || $this->eligibility->reasons($student) !== []) {
            throw ValidationException::withMessages(['student' => 'This student is not available for invitations.']);
        }

        if (! $internship->isAcceptingApplications()) {
            throw ValidationException::withMessages(['internship_id' => 'Choose an internship that is open and not expired.']);
        }

        if (InternshipApplication::withTrashed()->where('internship_id', $internship->id)->where('student_id', $student->id)->exists()) {
            throw ValidationException::withMessages(['internship_id' => 'This student already applied to that internship.']);
        }

        if ($this->hasPendingInvitation($student, $internship)) {
            throw ValidationException::withMessages(['internship_id' => 'You already invited this student to that internship.']);
        }

        $limit = (int) config('invitations.daily_limit', 20);
        if ($this->sentToday($employer) >= $limit) {
            throw ValidationException::withMessages(['internship_id' => "You can send up to {$limit} invitations a day. Try again tomorrow."]);
        }

        $expiresAt = now()->addDays((int) config('invitations.expires_after_days', 14));
        if ($internship->expires_at && $internship->expires_at->lt($expiresAt)) {
            $expiresAt = $internship->expires_at->copy();
        }

        try {
            $invitation = InternshipInvitation::create([
                'employer_id' => $employer->id,
                'student_id' => $student->id,
                'internship_id' => $internship->id,
                'invited_by_user_id' => $employerUser->id,
                'message' => filled($message) ? trim($message) : null,
                'status' => InvitationStatus::Pending,
                'expires_at' => $expiresAt,
            ]);
        } catch (QueryException) {
            // Two invites racing past the check above: the pending_key unique index stops the second.
            throw ValidationException::withMessages(['internship_id' => 'You already invited this student to that internship.']);
        }

        $this->activityLog->log($employerUser, 'invitation_send', ['invitation_id' => $invitation->id, 'student_id' => $student->id]);

        $this->notifications->send(
            $student->user,
            'invitation',
            'New internship invitation',
            "{$employer->company_name} invited you to apply for {$internship->title}.",
            ['invitation_id' => $invitation->id]
        );

        return $invitation;
    }

    /**
     * Creates the Reviewed application and links it. Safe against double clicks: the invitation row is
     * locked, and a second accept of the same invitation returns the application already made.
     *
     * @throws ValidationException
     */
    public function accept(InternshipInvitation $invitation, User $studentUser): InternshipApplication
    {
        $result = DB::transaction(function () use ($invitation, $studentUser) {
            $invitation = InternshipInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $invitation->load(['student', 'internship.employer.user']);

            if ($invitation->status === InvitationStatus::Accepted && $invitation->application_id) {
                return ['application' => $invitation->application, 'created' => false];
            }

            if (! $invitation->isPending()) {
                throw ValidationException::withMessages(['invitation' => 'This invitation is no longer open.']);
            }

            if ($invitation->hasLapsed()) {
                $this->markExpired($invitation);

                return ['expired' => true];
            }

            $resume = Resume::query()
                ->where('student_id', $invitation->student_id)
                ->whereNotNull('file_path')
                ->orderByDesc('generated_at')
                ->orderByDesc('id')
                ->first();
            if (! $resume) {
                throw ValidationException::withMessages(['resume' => 'Generate your resume first, then accept the invitation.']);
            }

            $application = $this->applications->applyFromInvitation($invitation, $resume, $studentUser);
            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'responded_at' => now(),
                'application_id' => $application->id,
            ]);

            DB::afterCommit(fn () => $this->notifyAccepted($invitation, $application));

            return ['application' => $application, 'created' => true];
        });

        if ($result['expired'] ?? false) {
            throw ValidationException::withMessages(['invitation' => 'This invitation has expired.']);
        }

        return $result['application'];
    }

    /**
     * @throws ValidationException
     */
    public function decline(InternshipInvitation $invitation): void
    {
        $declined = DB::transaction(function () use ($invitation) {
            $invitation = InternshipInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            if ($invitation->status === InvitationStatus::Declined) {
                return null;
            }
            if (! $invitation->isPending()) {
                throw ValidationException::withMessages(['invitation' => 'This invitation is no longer open.']);
            }

            $invitation->update(['status' => InvitationStatus::Declined, 'responded_at' => now()]);

            return $invitation;
        });

        if ($declined) {
            $declined->load(['student.user', 'internship.employer.user']);
            // No reason is collected or shown.
            $this->notifications->send(
                $declined->internship->employer->user,
                'invitation',
                'Invitation declined',
                "{$declined->student->user->name} declined your invitation for {$declined->internship->title}.",
                ['invitation_id' => $declined->id],
                email: false
            );
        }
    }

    /**
     * Run by invitations:expire. Expires lapsed invitations and reminds students shortly before expiry.
     *
     * @return array{expired: int, reminded: int}
     */
    public function expireAndRemind(): array
    {
        $expired = 0;
        InternshipInvitation::pending()
            ->with('internship')
            ->lazyById(100)
            ->each(function (InternshipInvitation $invitation) use (&$expired) {
                $updated = DB::transaction(function () use ($invitation) {
                    $locked = InternshipInvitation::query()->lockForUpdate()->find($invitation->id);
                    if (! $locked?->isPending() || ! $locked->load('internship')->hasLapsed()) {
                        return false;
                    }
                    $this->markExpired($locked);

                    return true;
                });
                $expired += $updated ? 1 : 0;
            });

        $reminded = 0;
        InternshipInvitation::pending()
            ->whereNull('reminded_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays((int) config('invitations.reminder_days_before', 2)))
            ->with(['student.user', 'internship.employer'])
            ->lazyById(100)
            ->each(function (InternshipInvitation $invitation) use (&$reminded) {
                $invitation->update(['reminded_at' => now()]);
                $this->notifications->send(
                    $invitation->student->user,
                    'invitation',
                    'Invitation expiring soon',
                    "Your invitation from {$invitation->internship->employer->company_name} for {$invitation->internship->title} expires on {$invitation->expires_at->format('M d, Y h:i A')}.",
                    ['invitation_id' => $invitation->id],
                    email: false
                );
                $reminded++;
            });

        return ['expired' => $expired, 'reminded' => $reminded];
    }

    public function sentToday(Employer $employer): int
    {
        return $employer->invitations()->where('created_at', '>=', now()->startOfDay())->count();
    }

    private function hasPendingInvitation(Student $student, Internship $internship): bool
    {
        return InternshipInvitation::pending()
            ->where('student_id', $student->id)
            ->where('internship_id', $internship->id)
            ->exists();
    }

    /** Must run inside a transaction holding the invitation's row lock. */
    private function markExpired(InternshipInvitation $invitation): void
    {
        $invitation->update(['status' => InvitationStatus::Expired]);
        $invitation->load(['student.user', 'internship.employer.user']);

        DB::afterCommit(function () use ($invitation) {
            $internship = $invitation->internship;
            $this->notifications->send(
                $invitation->student->user,
                'invitation',
                'Invitation expired',
                "Your invitation from {$internship->employer->company_name} for {$internship->title} has expired.",
                ['invitation_id' => $invitation->id],
                email: false
            );
            $this->notifications->send(
                $internship->employer->user,
                'invitation',
                'Invitation expired',
                "Your invitation to {$invitation->student->user->name} for {$internship->title} expired without a reply.",
                ['invitation_id' => $invitation->id],
                email: false
            );
        });
    }

    private function notifyAccepted(InternshipInvitation $invitation, InternshipApplication $application): void
    {
        $internship = $invitation->internship;
        $studentUser = $invitation->student->user;

        // Doubles as the "application reviewed, you can message" notice, so the student gets one, not two.
        $this->notifications->send(
            $studentUser,
            'application_update',
            'Invitation accepted',
            "You accepted the invitation from {$internship->employer->company_name} for {$internship->title}. The company will contact you about the interview. You can now message the company.",
            ['application_id' => $application->id, 'messaging_open_with' => $internship->employer->user_id]
        );

        // Employers get emails for other application events, so this one does too.
        $this->notifications->send(
            $internship->employer->user,
            'application',
            'Invitation accepted',
            "{$studentUser->name} accepted your invitation and applied for {$internship->title}.",
            ['application_id' => $application->id]
        );
    }
}
