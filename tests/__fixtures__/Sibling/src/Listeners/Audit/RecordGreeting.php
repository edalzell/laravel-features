<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Listeners\Audit;

use Edalzell\Features\Tests\Fixtures\Sibling\Events\GreetingSent;

class RecordGreeting
{
    public function handle(GreetingSent $event): void {}
}
