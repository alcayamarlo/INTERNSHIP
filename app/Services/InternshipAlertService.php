<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Internship;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Job alerts and deadline reminders for students.
 *
 * Who hears about a posting: active students in one of the posting's programs (or any
 * program when it lists none), who meet the school's eligibility rules, have no placement
 * yet, whose coordinator did not flag the posting, and whose competency match reaches
 * config('internships.alert_min_match'). The rules are fixed criteria, not AI.
 */
class InternshipAlertService
{
    public function __construct(
        private NotificationService $notifications,
        private CompetencyMatchingService $matching,
        private InternshipEligibility $eligibility,
    ) {}

    /** Tell matching students about a newly opened posting. Sent once per posting. */
    public function sendNewPostingAlerts(Internship $internship): int
    {
        $internship->refresh();

        if ($internship->alerts_sent_at !== null || ! $internship->isAcceptingApplications()) {
            return 0;
        }

        $internship->loadMissing('employer');
        $company = $internship->employer?->company_name ?? 'A partner company';
        $sent = 0;

        // A retried run picks up where the last one stopped.
        $alreadyNotified = $this->notifiedUserIds('internship_alert', $internship->id);

        foreach ($this->matchingStudents($internship) as ['student' => $student, 'match' => $match]) {
            if ($alreadyNotified->has($student->user_id)) {
                continue;
            }

            $deadline = $internship->expires_at ? ' Apply by '.$internship->expires_at->format('M d, Y').'.' : '';
            $matchText = $match !== null ? " It matches {$match}% of your competencies." : '';

            $this->notifications->send(
                $student->user,
                'internship_alert',
                'New internship: '.$internship->title,
                "{$company} posted \"{$internship->title}\".{$matchText}{$deadline}",
                ['internship_id' => $internship->id, 'match_percentage' => $match]
            );
            $sent++;
        }

        // Marked last: a run that fails or is killed partway must stay retryable.
        Internship::query()->whereKey($internship->id)->update(['alerts_sent_at' => now()]);

        return $sent;
    }

    /**
     * Remind matching students who have not applied, and the company, that the posting
     * closes soon. Sent once per deadline.
     *
     * @return array{students: int, employer: bool}
     */
    public function sendDeadlineReminder(Internship $internship): array
    {
        if ($internship->deadline_reminded_at !== null || $internship->expires_at === null) {
            return ['students' => 0, 'employer' => false];
        }

        $internship->loadMissing('employer.user');
        $deadline = $internship->expires_at->format('M d, Y');
        $deadlineKey = $internship->expires_at->toDateString();
        $company = $internship->employer?->company_name ?? 'A partner company';
        // Deleted applications count here because those students cannot apply again.
        $applied = $internship->applications()->withTrashed()->pluck('student_id')->all();
        $sent = 0;

        // A retried run picks up where the last one stopped; a new deadline starts over.
        $alreadyNotified = $this->notifiedUserIds('internship_deadline', $internship->id, $deadlineKey);

        foreach ($this->matchingStudents($internship) as ['student' => $student]) {
            if (in_array($student->id, $applied, true) || $alreadyNotified->has($student->user_id)) {
                continue;
            }

            $this->notifications->send(
                $student->user,
                'internship_deadline',
                'Closing soon: '.$internship->title,
                "Applications for \"{$internship->title}\" at {$company} close on {$deadline}. Apply before the deadline if you are interested.",
                ['internship_id' => $internship->id, 'deadline' => $deadlineKey]
            );
            $sent++;
        }

        $employerUser = $internship->employer?->user;
        $notifyEmployer = $employerUser && $employerUser->is_active && $employerUser->archived_at === null
            && ! $alreadyNotified->has($employerUser->id);
        if ($notifyEmployer) {
            $count = $internship->applications()->count();
            $this->notifications->send(
                $employerUser,
                'internship_deadline',
                'Posting closes soon: '.$internship->title,
                "Your posting \"{$internship->title}\" stops accepting applications on {$deadline}. It has {$count} application(s) so far. Edit the posting to extend the deadline.",
                ['internship_id' => $internship->id, 'deadline' => $deadlineKey]
            );
        }

        // Marked last: a run that fails or is killed partway must stay retryable.
        Internship::query()->whereKey($internship->id)->update(['deadline_reminded_at' => now()]);

        return ['students' => $sent, 'employer' => $notifyEmployer];
    }

    /** @return Collection<int, int> keyed by user id, including notifications the user has since deleted */
    private function notifiedUserIds(string $type, int $internshipId, ?string $deadline = null): Collection
    {
        return AppNotification::withTrashed()
            ->where('type', $type)
            ->where('data->internship_id', $internshipId)
            ->when($deadline !== null, fn (Builder $query) => $query->where('data->deadline', $deadline))
            ->pluck('user_id')
            ->flip();
    }

    /** @return Collection<int, array{student: Student, match: int|null}> */
    public function matchingStudents(Internship $internship): Collection
    {
        $internship->loadMissing('programs', 'requirementsList');
        $programIds = $internship->programs->pluck('id')->all();
        $hasRequirements = $internship->requirementsList->isNotEmpty();
        $minMatch = (int) config('internships.alert_min_match', 50);

        $query = Student::query()
            ->with('user')
            ->whereHas('user', fn (Builder $user) => $user->where('is_active', true)->whereNull('archived_at'))
            ->when($programIds !== [], fn (Builder $q) => $q->whereIn('program_id', $programIds))
            ->whereDoesntHave('placements');
        $this->eligibility->constrain($query);

        $flaggedBy = $internship->flags()->pluck('coordinator_id')->all();

        return $query->get()
            ->reject(fn (Student $student) => $flaggedBy !== []
                && in_array($student->assignedCoordinator()?->id, $flaggedBy, true))
            ->map(fn (Student $student) => [
                'student' => $student,
                'match' => $hasRequirements ? $this->matching->freshMatchPercentage($student, $internship) : null,
            ])
            ->filter(fn (array $row) => $row['match'] === null || $row['match'] >= $minMatch)
            ->values();
    }
}
