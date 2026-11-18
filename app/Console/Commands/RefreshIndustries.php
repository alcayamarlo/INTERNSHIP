<?php

namespace App\Console\Commands;

use App\Services\IndustryCatalog;
use Illuminate\Console\Command;

class RefreshIndustries extends Command
{
    protected $signature = 'industries:refresh';

    protected $description = 'Reload the full industry list (all NAICS levels) for the company forms from the API now; takes about 3 minutes';

    public function handle(IndustryCatalog $catalog): int
    {
        $result = $catalog->refresh();
        if ($result !== 'api') {
            $this->warn("Not refreshed: {$result}. The company forms keep using the last list that worked, or the stored one.");

            return self::FAILURE;
        }

        $this->info('Industry list reloaded from the API ('.count($catalog->all()).' industries).');

        return self::SUCCESS;
    }
}
