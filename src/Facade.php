<?php

namespace Edalzell\Features;

use Illuminate\Support\Facades\Facade as LaravelFacade;

/**
 * @mixin \Edalzell\Features\Features
 */
class Facade extends LaravelFacade
{
    protected static function getFacadeAccessor(): string
    {
        return Features::class;
    }
}
