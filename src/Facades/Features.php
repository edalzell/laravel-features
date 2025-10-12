<?php

namespace SilentZ\Features\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SilentZ\Features\Features
 */
class Features extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SilentZ\Features\Features::class;
    }
}
