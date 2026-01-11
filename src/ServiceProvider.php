<?php

namespace Edalzell\Features;

use Edalzell\Features\Console\Commands\Make;
use Edalzell\Features\Facade as Features;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    public function register()
    {
        Features::register($this->app);
    }
}
