<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Listeners;

use Edalzell\Features\Attributes\ListensTo;
use Edalzell\Features\Tests\Fixtures\Sibling\Events\GreetingSent;

#[ListensTo('greetings.sent', 'greetings.read')]
class LogGreeting
{
    public function handle(GreetingSent $event): void {}
}
