<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Subscribers;

class OrderSubscriber
{
    public function onCancelled(): void {}

    /** @return array<string, string> */
    public function subscribe(): array
    {
        return ['cancellations.created' => 'onCancelled'];
    }
}
