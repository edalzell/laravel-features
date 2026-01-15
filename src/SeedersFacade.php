<?php

namespace Edalzell\Features;

use Illuminate\Support\Facades\Facade;

class SeedersFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Seeders::class;
    }
}
