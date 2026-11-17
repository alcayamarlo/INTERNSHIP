<?php

namespace App\Console\Commands;

use App\Services\IndustryCatalog;
use Illuminate\Console\Command;

class RefreshIndustries extends Command
{
    protected $signature = 'industries:refresh';

    protected $description = 'Reload the industry list for the company forms from the API now';

    public function handle(IndustryCatalog $catalog): int
    {
        $result = $catalog->refresh();
        if ($result !== 'api') {
            $this->warn("Not refreshed: {$result}. The company forms keep using the last list that worked, or the stored one.");

            return self::FAILURE;
        }

        $this->info('Industry list reloaded from the API ('.count($catalog->sectors()).' sectors).');

        return self::SUCCESS;
    }
}
