<?php

namespace SilentZ\Features\Commands;

use Illuminate\Console\Command;

class FeaturesCommand extends Command
{
    public $signature = 'laravel-features';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
