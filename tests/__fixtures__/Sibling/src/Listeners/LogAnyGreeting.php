<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Listeners;

use Edalzell\Features\Attributes\ListensTo;

#[ListensTo('greetings.*')]
class LogAnyGreeting
{
    public function handle(string $event): void
    {
        app('heard')->push($event);
    }
}
