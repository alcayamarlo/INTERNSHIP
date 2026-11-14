<?php

namespace App\Console\Commands;

use App\Models\Placement;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OjtDeadlineService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reminds whoever's turn it is, by bell and email (NotificationService honours the email opt-out):
 * - weekly report: the student, 1 day before due and once when overdue;
 * - monthly evaluation: the student (rating, then comment) or the supervisor, 2 days before and once overdue;
 * - final evaluation: the supervisor, 2 days before and once overdue.
 * Only active placements. Each reminder is recorded in ojt_reminders first, so it is never sent twice.
 */
class RemindOjtDocuments extends Command
{
    protected $signature = 'ojt:remind-documents';

    protected $description = 'Remind students and supervisors about OJT documents that are due soon or overdue';

    public function handle(OjtDeadlineService $deadlines, NotificationService $notifications): int
    {
        $today = OjtDeadlineService::today();
        $sent = 0;
        $failed = 0;

        $placements = Placement::query()
            ->where('status', Placement::STATUS_ACTIVE)
            ->with(['student.user', 'employer.user'])
            ->get();

        foreach ($placements as $placement) {
            foreach ($deadlines->pendingFor($placement, $today) as $item) {
                $recipient = match ($item['actor']) {
                    'student' => $placement->student?->user,
                    'supervisor' => $placement->employer?->user,
                    default => null, // coordinators see the badges; they are not reminded here
                };
                $stage = $this->stage($item, $today);
                if (! $recipient instanceof User || $stage === null) {
                    continue;
                }

                $claimed = DB::table('ojt_reminders')->insertOrIgnore([
                    'placement_id' => $placement->id,
                    'kind' => $item['kind'],
                    'subject_key' => $item['subject_key'],
                    'stage' => $stage,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($claimed === 0) {
                    continue; // already reminded at this stage
                }

                try {
                    [$title, $message] = $this->text($item, $stage, $placement);
                    $notifications->send($recipient, 'ojt_reminder', $title, $message, [
                        'placement_id' => $placement->id,
                        'kind' => $item['kind'],
                        'due_on' => $item['due_on']->toDateString(),
                    ]);
                    $sent++;
                } catch (\Throwable $exception) {
                    // Let the next run try again.
                    DB::table('ojt_reminders')->where([
                        'placement_id' => $placement->id, 'kind' => $item['kind'], 'subject_key' => $item['subject_key'], 'stage' => $stage,
                    ])->delete();
                    report($exception);
                    $failed++;
                }
            }
        }

        $this->info("Sent {$sent} OJT document reminder(s), {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** "<step>:overdue" once past due, "<step>:before_due" within the reminder window, else null. */
    private function stage(array $item, CarbonImmutable $today): ?string
    {
        if ($item['overdue']) {
            return $item['step'].':overdue';
        }

        return $today->gte($item['due_on']->subDays($item['days_before'])) ? $item['step'].':before_due' : null;
    }

    /** @return array{0: string, 1: string} */
    private function text(array $item, string $stage, Placement $placement): array
    {
        $due = $item['due_on']->format('M d, Y');
        $overdue = str_ends_with($stage, ':overdue');
        $student = $placement->student?->user?->name ?? 'your intern';

        return match ($item['kind']) {
            OjtDeadlineService::WEEKLY_REPORT => $overdue
                ? ['Weekly report overdue', "Your {$item['label']} (activity narrative report) was due {$due}. Please submit it as soon as possible."]
                : ['Weekly report due soon', "Your {$item['label']} (activity narrative report) is due {$due}."],
            OjtDeadlineService::MONTHLY_EVALUATION => match ($item['step']) {
                'supervisor_rating' => [$overdue ? 'Monthly evaluation overdue' : 'Monthly evaluation due soon',
                    "Please rate {$student} on the {$item['label']}. ".($overdue ? "It was due {$due}." : "Due {$due}.")],
                'student_comment' => [$overdue ? 'Monthly evaluation comment overdue' : 'Add your monthly evaluation comment',
                    "Add your comment on the {$item['label']}. ".($overdue ? "It was due {$due}." : "Due {$due}.")],
                default => [$overdue ? 'Monthly evaluation overdue' : 'Monthly evaluation due soon',
                    "Rate yourself on the {$item['label']}. ".($overdue ? "It was due {$due}." : "Due {$due}.")],
            },
            default => [$overdue ? 'Final evaluation overdue' : 'Final evaluation due soon',
                "Please submit the Final Performance Evaluation for {$student}. ".($overdue ? "It was due {$due}." : "Due {$due}.")],
        };
    }
}
