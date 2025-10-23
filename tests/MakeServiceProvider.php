<?php

namespace Edalzell\Features\Tests;

use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\ServiceProvider;

class MakeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    public function register() {}
}
