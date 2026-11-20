<?php

namespace Edalzell\Features\Tests\Fixtures\Orders\Subscribers\Audit;

class RefundSubscriber
{
    public function onRefunded(): void {}

    /** @return array<string, string> */
    public function subscribe(): array
    {
        return ['refunds.created' => 'onRefunded'];
    }
}
