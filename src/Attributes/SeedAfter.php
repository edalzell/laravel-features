<?php

namespace Edalzell\Features\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SeedAfter
{
    /** @var array<int, class-string> */
    public readonly array $seeders;

    /** @param class-string ...$seeders */
    public function __construct(string ...$seeders)
    {
        $this->seeders = $seeders;
    }
}
