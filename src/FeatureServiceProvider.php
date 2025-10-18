<?php

namespace SilentZ\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use SilentZ\Features\Concerns\Provider\SupportsConfiguration;
use SilentZ\Features\Concerns\Provider\SupportsDatabase;
use Spatie\Blink\Blink;
use Symfony\Component\Filesystem\Path;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    use SupportsConfiguration, SupportsDatabase;

    public function boot()
    {
        $this->bootConfiguration($this->feature());
    }

    public function register()
    {
        $this->registerConfiguration($this->feature());
        $this->registerMigrations($this->feature());
    }

    private function disk(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => app_path('Features/'.$this->feature()->name),
        ]);
    }

    private function feature(): Feature
    {
        $blink = new Blink;

        return $blink->once('feature', fn () => $this->resolveFeature());
    }

    private function resolveFeature(): Feature
    {
        $class = new \ReflectionClass(static::class);
        $pathParts = explode('/', Path::canonicalize($class->getFileName()));

        // /.../app/Features/One/src/ServiceProvider.php
        $name = $pathParts[count($pathParts) - 3];

        return new Feature($name);
    }
}
