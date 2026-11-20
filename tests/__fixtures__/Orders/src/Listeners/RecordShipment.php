<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

class RecordShipment
{
    public function handle(): void
    {
        app('heard')->push(self::class);
    }
}
