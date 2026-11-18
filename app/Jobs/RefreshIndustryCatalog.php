<?php

namespace App\Jobs;

use App\Services\IndustryCatalog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Reloads the industry list from the API in the background, so no page waits on it. */
class RefreshIndustryCatalog implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 400;

    public function handle(IndustryCatalog $catalog): void
    {
        $catalog->refresh();
    }
}
