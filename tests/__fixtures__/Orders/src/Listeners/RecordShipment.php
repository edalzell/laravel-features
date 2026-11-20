<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

use Edalzell\Features\Attributes\ListensTo;

#[ListensTo('orders.shipped', 'orders.delivered')]
class RecordShipment
{
    public function handle(): void
    {
        app('heard')->push(self::class);
    }
}
