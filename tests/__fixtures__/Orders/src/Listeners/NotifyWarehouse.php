<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

use Edalzell\Features\Tests\Fixtures\Orders\Events\OrderPlaced;

class NotifyWarehouse
{
    public function handle(OrderPlaced $event): void
    {
        app('heard')->push(self::class);
    }
}
