<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

use Edalzell\Features\Attributes\ListensTo;

#[ListensTo('orders.*')]
class RecordOrderEvent
{
    public function handle(string $event): void
    {
        app('heard')->push($event);
    }
}
