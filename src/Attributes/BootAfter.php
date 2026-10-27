<?php

namespace Edalzell\Features\Attributes;

use Attribute;
use Illuminate\Support\ServiceProvider;

#[Attribute(Attribute::TARGET_CLASS)]
class BootAfter
{
    /** @param  class-string<ServiceProvider>  $provider */
    public function __construct(public readonly string $provider) {}
}
