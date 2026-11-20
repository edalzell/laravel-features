<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Subscribers\Audit;

class AuditSubscriber
{
    public function onAudited(): void {}

    /** @return array<string, string> */
    public function subscribe(): array
    {
        return ['greetings.audited' => 'onAudited'];
    }
}
