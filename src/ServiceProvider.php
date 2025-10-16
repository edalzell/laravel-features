<?php

namespace SilentZ\Features;

use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->registerFeatureProviders();
    }

    private function addNamespaces(): void
    {
        $loader = new ClassLoader;
        $loader->addPsr4('App\\Features\\One\\Database\\Seeders\\', app_path('Features/One/database/check/'));
        $loader->register();
    }

    private function disk(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => app_path('Features'),
        ]);
    }

    private function registerFeatureProviders(): void
    {
        if (empty($features = $this->disk()->directories())) {
            return;
        }

        foreach ($features as $feature) {
            if ($this->disk()->exists($feature.DIRECTORY_SEPARATOR.'ServiceProvider.php')) {
                $this->app->register('App\\Features\\'.$feature.'\\ServiceProvider');
            }
        }
    }
}
