<?php

namespace Edalzell\Features\Providers;

use Edalzell\Features\Seeders;
use Edalzell\Features\SeedersFacade;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use ReflectionClass;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    protected Filesystem $disk;

    protected string $name;

    protected ReflectionClass $reflection;

    protected array $seeders = [];

    public function __construct($app)
    {
        parent::__construct($app);

        $this->reflection = new ReflectionClass(static::class);

        $this->name = $this->name();
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
            ->registerSeeders()
            ->registerViews();
    }

    protected function bootConfig(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $configFile = $this->slug().'.php';

        if (! $this->disk()->exists($path = 'config/'.$configFile)) {
            return $this;
        }

        $this->publishes(
            [$path => config_path($configFile)],
            $this->slug().'-config'
        );

        return $this;
    }

    protected function bootListeners(): self
    {
        foreach ($this->discoverEvents() as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }

        return $this;
    }

    protected function bootSeeders(): self
    {
        SeedersFacade::add($this->seeders);

        return $this;
    }

    protected function featuresPath(): string
    {
        return base_path('features/'.$this->name);
    }

    protected function name(): string
    {
        $pathParts = explode('/', Path::normalize($this->reflection->getFileName()));

        // /.../app/Features/One/src/ServiceProvider.php
        return $pathParts[count($pathParts) - 3];
    }

    protected function namespace(): string
    {
        return str($this->reflection->getNamespaceName())->replaceEnd('\ServiceProvider', '');
    }

    protected function registerConfig(): self
    {
        if (! $this->disk()->exists($path = 'config/'.$this->slug().'.php')) {
            return $this;
        }

        $this->mergeConfigFrom($this->disk()->path($path), $this->slug());

        return $this;
    }

    protected function registerMigrations(): self
    {
        if (! $this->disk()->exists('database/migrations')) {
            return $this;
        }

        $this->loadMigrationsFrom($this->disk()->path('database/migrations'));

        return $this;
    }

    protected function registerRoutes(): self
    {
        if (! $this->disk()->exists('routes')) {
            return $this;
        }

        collect($this->finder('routes'))
            ->map(fn (SplFileInfo $file) => $file->getRealPath())
            ->filter()
            ->each(fn (string $routePath) => $this->loadRoutesFrom($routePath));

        return $this;
    }

    protected function registerSeeders(): self
    {
        /*
            Make this a singleton so that when db seeders (in the app) call it,
            it gets the same instance where the feature seeders were registered
        */
        $this->app->singleton(Seeders::class, fn () => new Seeders);

        return $this;
    }

    protected function registerViews(): self
    {
        if (! $this->disk()->exists('resources/views')) {
            return $this;
        }

        $this->loadViewsFrom($this->disk()->path('resources/views'), $this->slug());

        return $this;
    }

    protected function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }

    private function discoverEvents(): array
    {
        if (! $this->disk()->exists('src/Listeners')) {
            return [];
        }

        DiscoverEvents::guessClassNamesUsing(
            // @phpstan-ignore-next-line
            fn (SplFileInfo $file, $ignored): string => "{$this->namespace()}\\Listeners\\".$file->getBasename('.php'),
        );

        $events = DiscoverEvents::within($this->disk()->path('src/Listeners'), '');

        return $events;
    }

    private function disk(): Filesystem
    {
        if (! isset($this->disk)) {
            $this->disk = Storage::build([
                'driver' => 'local',
                'root' => $this->featuresPath(),
            ]);
        }

        return $this->disk;
    }

    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->disk->path($path))->name('*.php');
    }
}
