<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    protected array $seeders = [];

    private Filesystem $disk;

    private string $name;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->name = $this->name();

        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => base_path('features/'.$this->name),
        ]);
    }

    public function boot()
    {
        $this
            ->bootConfig()
            ->bootListeners()
            ->bootSeeders();
    }

    public function register()
    {
        $this
            ->registerConfig()
            ->registerMigrations()
            ->registerRoutes()
            ->registerViews();
    }

    private function bootConfig(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $configFile = $this->slug().'.php';

        if (! $this->disk->exists($path = 'config/'.$configFile)) {
            return $this;
        }

        $this->publishes(
            [$path => config_path($configFile)],
            $this->slug().'-config'
        );

        return $this;
    }

    protected function bootSeeders(): self
    {
        SeedersFacade::add($this->seeders);

        return $this;
    }

    protected function registerConfig(): self
    {
        if (! $this->disk->exists($path = 'config/'.$this->slug().'.php')) {
            return $this;
        }

        $this->mergeConfigFrom($this->disk->path($path), $this->slug());

        return $this;
    }

    protected function bootListeners(): self
    {
        DiscoverEvents::guessClassNamesUsing(
            fn (SplFileInfo $file) => "Features\\{$this->name}\\Listeners\\".$file->getBasename('.php')
        );

        $events = DiscoverEvents::within($this->disk->path('src/Listeners'), '');

        DiscoverEvents::$guessClassNamesUsingCallback = null;

        foreach ($events as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }

        return $this;
    }

    protected function registerMigrations(): self
    {
        if (! $this->disk->exists('database/migrations')) {
            return $this;
        }

        $this->loadMigrationsFrom($this->disk->path('database/migrations'));

        return $this;
    }

    protected function registerRoutes(): self
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

    protected function registerViews(): self
    {
        if (! $this->disk->exists('resources/views')) {
            return $this;
        }

        $this->loadViewsFrom($this->disk->path('resources/views'), $this->slug());

        return $this;
    }

    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->disk->path($path))->name('*.php');
    }

    protected function name(): string
    {
        $class = new \ReflectionClass(static::class);
        $pathParts = explode('/', Path::normalize($class->getFileName()));

        // /.../app/Features/One/src/ServiceProvider.php
        return $pathParts[count($pathParts) - 3];
    }

    private function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }
}
