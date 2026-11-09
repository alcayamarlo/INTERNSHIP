<?php

namespace App\Jobs;

use App\Models\Internship;
use App\Services\InternshipAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/** Job alerts for a newly opened posting, scored outside the employer's request. */
class SendInternshipAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $internshipId) {}

    /** Two quick saves of one posting must not both send. The lock clears itself if a worker dies. */
    public function middleware(): array
    {
        return [(new WithoutOverlapping((string) $this->internshipId))->expireAfter($this->timeout)->dontRelease()];
    }

    public function handle(InternshipAlertService $alerts): void
    {
        $internship = Internship::find($this->internshipId);

        if ($internship) {
            $alerts->sendNewPostingAlerts($internship);
        }
    }
}
