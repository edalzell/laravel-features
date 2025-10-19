<?php

namespace SilentZ\Features;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Spatie\Blink\Blink;
use Symfony\Component\Filesystem\Path;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        $this->feature()->boot();
    }

    public function register()
    {
        $this->feature()->register();
    }

    public function feature(): Feature
    {
        return (new Blink)->once('feature', fn () => $this->resolveFeature());
    }

    public function loadMigrations(string $path): void
    {
        $this->loadMigrationsFrom($path);
    }

    public function loadRoutes(string $path): void
    {
        $this->loadRoutesFrom($path);
    }

    public function loadViews(string $path, string $namespace): void
    {
        $this->loadViewsFrom($path, $namespace);
    }

    public function mergeConfig(string $path, string $key): void
    {
        $this->mergeConfigFrom($path, $key);
    }

    public function publish(array $paths, ?string $tag = null): void
    {
        $this->publishes($paths, $tag);
    }

    private function resolveFeature(): Feature
    {
        $class = new \ReflectionClass(static::class);
        $pathParts = explode('/', Path::canonicalize($class->getFileName()));

        // /.../app/Features/One/src/ServiceProvider.php
        $name = $pathParts[count($pathParts) - 3];

        return new Feature($name, $this);
    }
}
