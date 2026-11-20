<?php

namespace Edalzell\Features\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ListensTo
{
    /** @var array<int, string> */
    public readonly array $events;

    public function __construct(string ...$events)
    {
        $this->events = array_values($events);
    }
}
