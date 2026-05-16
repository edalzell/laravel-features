<?php

namespace Edalzell\Features;

use Edalzell\Features\Concerns\HasFeatures;
use Edalzell\Features\Console\Commands\Load;
use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    use HasFeatures;

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    public function register(): void
    {
        $this->registerFeatures(base_path('features'), 'Features');
    }
}
