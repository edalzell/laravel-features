<?php

namespace Edalzell\Features;

use Edalzell\Features\Concerns\HasFeatures;
use Edalzell\Features\Console\Commands\Make;
use Edalzell\Features\Extensions\LivewireComponents;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    use HasFeatures;

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    public function register(): void
    {
        $this->app->singleton(FeatureRegistry::class);

        $this->app->tag(LivewireComponents::class, Extension::TAG);

        $this->registerFeatures(base_path('features'), 'Features');
    }
}
