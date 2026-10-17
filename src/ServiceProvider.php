<?php

namespace Edalzell\Features;

use Edalzell\Features\Concerns\HasFeatures;
use Edalzell\Features\Console\Commands\Make;
use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\Extensions\LivewireComponents;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    use HasFeatures;

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);

            $this->publishes([
                __DIR__.'/../config/features.php' => config_path('features.php'),
            ], 'features-config');
        }
    }

    public function register(): void
    {
        $this->app->singleton(FeatureRegistry::class);

        $this->mergeConfigFrom(__DIR__.'/../config/features.php', 'features');

        $this->app->tag(LivewireComponents::class, Extension::TAG);

        $this->registerFeatures(base_path('features'), 'Features');
    }
}
