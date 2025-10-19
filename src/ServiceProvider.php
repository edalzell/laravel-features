<?php

namespace Edalzell\Features;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Features'),
        ]);

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
