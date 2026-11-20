<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Subscribers;

class GreetingSubscriber
{
    public function onArchived(): void {}

    /** @return array<string, string> */
    public function subscribe(): array
    {
        return ['greetings.archived' => 'onArchived'];
    }
}
