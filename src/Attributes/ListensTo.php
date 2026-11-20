<?php

namespace Edalzell\Features\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ListensTo
{
    public function __construct(public readonly string $event) {}
}
