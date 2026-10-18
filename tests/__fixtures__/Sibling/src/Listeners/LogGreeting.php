<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Listeners;

use Edalzell\Features\Tests\Fixtures\Sibling\Events\GreetingSent;

class LogGreeting
{
    public function handle(GreetingSent $event): void {}
}
