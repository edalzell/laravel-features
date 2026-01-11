<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    private Filesystem $disk;

    private string $name;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->name = $this->name();

        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Features/'.$this->name),
        ]);
    }

    public function boot()
    {
        $this
            ->bootConfig();
    }

    public function register()
    {
        $this
            ->registerConfig()
            ->registerDatabase()
            ->registerRoutes()
            ->registerViews();
    }

    private function bootConfig(): self
    {
        $configFile = $this->slug().'.php';
        if ($this->app->runningInConsole() && $this->disk->exists($path = 'config/'.$configFile)) {
            $this->publishes(
                [$path => config_path($configFile)],
                $this->slug().'-config'
            );
        }

        return $this;
    }

    private function name(): string
    {
        $class = new \ReflectionClass(static::class);
        $pathParts = explode('/', Path::normalize($class->getFileName()));

        // /.../app/Features/One/src/ServiceProvider.php
        return $pathParts[count($pathParts) - 3];
    }

    public function registerConfig(): self
    {
        if ($this->disk->exists($path = 'config/'.$this->slug().'.php')) {
            $this->mergeConfigFrom($this->disk->path($path), $this->slug());
        }

        return $this;
    }

    private function registerDatabase(): self
    {
        if ($this->disk->exists('database/migrations')) {
            $this->loadMigrationsFrom($this->disk->path('database/migrations'));
        }

        return $this;
    }

    public function registerRoutes(): static
    {
        if (! $this->disk->exists('routes')) {
            return $this;
        }

        collect($this->finder('routes'))
            ->map(fn (SplFileInfo $file) => $file->getRealPath())
            ->filter()
            ->each(fn (string $routePath) => $this->loadRoutesFrom($routePath));

        return $this;
    }

    private function registerViews(): self
    {
        if ($this->disk->exists('resources/views')) {
            $this->loadViewsFrom($this->disk->path('resources/views'), $this->slug());
        }

        return $this;
    }

    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->disk->path($path))->name('*.php');
    }

    private function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }
}
