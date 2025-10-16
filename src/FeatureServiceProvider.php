<?php

namespace SilentZ\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use SilentZ\Features\Concerns\Provider\SupportsConfiguration;
use Spatie\Blink\Blink;
use Symfony\Component\Filesystem\Path;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    use SupportsConfiguration;

    public function boot()
    {
        $this->bootConfiguration($this->feature());
    }

    public function register()
    {
        $this->registerConfiguration($this->feature());
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

        // /.../app/Features/One/ServiceProvider.php
        $name = $pathParts[count($pathParts) - 2];

        return new Feature($name);
    }
}
