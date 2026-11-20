<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Listeners;

class RecordOrderEvent
{
    public function handle(string $event): void
    {
        app('heard')->push($event);
    }
}
