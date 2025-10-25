<?php

namespace Edalzell\Features;

use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\Facades\Storage;
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
        if (! file_exists($featuresDir = app_path('Features'))) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => $featuresDir]);

        if (empty($features = $disk->directories())) {
            return;
        }

        foreach ($features as $feature) {
            if ($disk->exists($feature.'/src/ServiceProvider.php')) {
                $this->app->register('App\\Features\\'.$feature.'\\ServiceProvider');
            }
        }
    }
}
