<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

use Edalzell\Features\Attributes\ListensTo;
use Edalzell\Features\Tests\Fixtures\Orders\Events\OrderPlaced;

#[ListensTo('orders.restocked')]
class NotifyWarehouse
{
    public function handle(?OrderPlaced $event = null): void
    {
        app('heard')->push(self::class);
    }
}
