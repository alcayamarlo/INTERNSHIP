<?php

namespace App\Services;

use App\Events\LiveUpdate;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Student;
use App\Models\User;

use function Illuminate\Support\defer;

/**
 * Collects who should see a live refresh during one request (or queue job) and sends one
 * LiveUpdate per group of users at the end, after the response has gone to the browser.
 *
 * Only active with a real broadcaster AND a queue worker (QUEUE_CONNECTION other than sync):
 * with sync, every push would be a Pusher HTTP call inside the user's click, which is exactly
 * the lag this avoids. Without it, pages still refresh on new notifications.
 */
class LiveUpdates
{
    /** Pusher allows up to 100 channels per push; stay below it. */
    private const CHANNELS_PER_PUSH = 90;

    /** @var array<int, array<string, true>> user id => set of topics */
    private array $pending = [];

    /** @var list<int>|null */
    private ?array $adminIds = null;

    public function enabled(): bool
    {
        return in_array(config('broadcasting.default'), ['pusher', 'reverb', 'ably'], true)
            && config('queue.default') !== 'sync';
    }

    /**
     * Everyone who can see a record about this student: the student, the company involved,
     * the coordinators of the student's institution, and the administrators.
     */
    public function forStudentRecord(?int $studentId, ?int $employerId, string $topic): void
    {
        if (! $this->enabled() || ! $studentId) {
            return;
        }

        $student = Student::withTrashed()->whereKey($studentId)->first(['id', 'user_id', 'institution_id']);
        $userIds = [$student?->user_id, $employerId ? Employer::withTrashed()->whereKey($employerId)->value('user_id') : null];

        if ($student?->institution_id) {
            array_push($userIds, ...Coordinator::query()->where('institution_id', $student->institution_id)->pluck('user_id')->all());
        }

        $this->queue([...$userIds, ...$this->adminIds()], $topic);
    }

    /** Sends the collected pings: users with the same topics share one push. */
    public function flush(): void
    {
        $pending = $this->pending;
        $this->pending = [];

        $groups = [];
        foreach ($pending as $userId => $topics) {
            $groups[implode(',', array_keys($topics))][] = $userId;
        }

        foreach ($groups as $topics => $userIds) {
            foreach (array_chunk($userIds, self::CHANNELS_PER_PUSH) as $chunk) {
                try {
                    broadcast(new LiveUpdate($chunk, explode(',', $topics)));
                } catch (\Throwable $exception) {
                    // A live refresh is a convenience; the saved data is already correct.
                    report($exception);
                }
            }
        }
    }

    private function queue(array $userIds, string $topic): void
    {
        foreach (array_filter($userIds) as $userId) {
            $this->pending[(int) $userId][$topic] = true;
        }

        // Same name every time, so one flush runs per request/job however many records changed.
        defer(fn () => $this->flush(), 'live-updates');
    }

    /** @return list<int> */
    private function adminIds(): array
    {
        return $this->adminIds ??= User::query()
            ->where('role', 'admin')
            ->whereNull('archived_at')
            ->pluck('id')
            ->all();
    }
}
