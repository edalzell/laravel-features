<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Features
{
    private readonly Application $app;

    private string $name;

    private string $configFileName;

    private string $configGroup = '';

    private string $configPublishHandle;

    private array $seeders = [];

    private ?Filesystem $disk = null;

    private string $path;

    private string $namespace;

    public function __construct(private readonly ServiceProvider $provider)
    {
        $reflection = new ReflectionClass($provider);

        $this->app = (new ReflectionProperty($provider, 'app'))->getValue($provider);
        $this->path = dirname($reflection->getFileName(), 2);
        $this->namespace = $reflection->getNamespaceName();
        $this->name = basename($this->path);
        $this->configFileName = $this->slug();
        $this->configPublishHandle = $this->slug();
    }

    public function path(string $path): static
    {
        $this->path = $path;
        $this->disk = null;

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        $this->configFileName = $this->slug();
        $this->configPublishHandle = $this->slug();

        return $this;
    }

    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function configFileName(string $name): static
    {
        $this->configFileName = $name;

        return $this;
    }

    public function configGroup(string $group): static
    {
        $this->configGroup = $group;

        return $this;
    }

    public function configPublishHandle(string $handle): static
    {
        $this->configPublishHandle = $handle;

        return $this;
    }

    public function seeders(array $seeders): static
    {
        $this->seeders = $seeders;

        return $this;
    }

    public function bootFeature(): void
    {
        $this
            ->bootConfig()
            ->bootListeners()
            ->bootPolicies()
            ->bootSeeders();
    }

    public function registerFeature(): void
    {
        $this
            ->registerConfig()
            ->registerMigrations()
            ->registerRoutes()
            ->registerSeeders()
            ->registerViews();
    }

    public function bootConfig(): static
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $configFile = $this->configFileName.'.php';

        if (! $this->disk()->exists($path = 'config/'.$configFile)) {
            return $this;
        }

        $this->callProtected(
            'publishes',
            [$this->disk()->path($path) => config_path($this->join('/', $this->configGroup, $configFile))],
            $this->join('-', $this->configPublishHandle, 'config'),
        );

        return $this;
    }

    public function bootListeners(): static
    {
        foreach ($this->discoverEvents() as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }

        return $this;
    }

    public function bootPolicies(): static
    {
        $this
            ->discoverPolicies()
            ->each(fn (string $policy, string $model) => Gate::policy($model, $policy));

        return $this;
    }

    public function bootSeeders(): static
    {
        SeedersFacade::add($this->seeders);

        return $this;
    }

    public function registerConfig(): static
    {
        if (! $this->disk()->exists('config/'.$this->configFileName.'.php')) {
            return $this;
        }

        $path = $this->join('/', 'config', $this->configGroup, $this->configFileName.'.php');

        $this->callProtected('mergeConfigFrom', $this->disk()->path($path), $this->configFileName);

        return $this;
    }

    public function registerMigrations(): static
    {
        if (! $this->disk()->exists('database/migrations')) {
            return $this;
        }

        $this->callProtected('loadMigrationsFrom', $this->disk()->path('database/migrations'));

        return $this;
    }

    public function registerRoutes(): static
    {
        if (! $this->disk()->exists('routes')) {
            return $this;
        }

        collect($this->finder('routes'))
            ->map(fn (SplFileInfo $file) => $file->getRealPath())
            ->filter()
            ->each(fn (string $routePath) => $this->callProtected('loadRoutesFrom', $routePath));

        return $this;
    }

    public function registerSeeders(): static
    {
        if (! $this->app->bound(Seeders::class)) {
            $this->app->singleton(Seeders::class, fn () => new Seeders);
        }

        return $this;
    }

    public function registerViews(): static
    {
        if (! $this->disk()->exists('resources/views')) {
            return $this;
        }

        $this->callProtected('loadViewsFrom', $this->disk()->path('resources/views'), $this->slug());

        return $this;
    }

    private function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }

    /** @return array<string, array<string>> */
    private function discoverEvents(): array
    {
        if (! $this->disk()->exists('src/Listeners')) {
            return [];
        }

        // guessClassNamesUsing() sets a static callback on DiscoverEvents. This is safe
        // because within() is called immediately after, before any other feature's
        // bootListeners() runs. Do not move these two calls apart.
        DiscoverEvents::guessClassNamesUsing(
            // @phpstan-ignore-next-line
            fn (SplFileInfo $file, $_ignored): string => "{$this->namespace}\\Listeners\\".$file->getBasename('.php'),
        );

        return DiscoverEvents::within($this->disk()->path('src/Listeners'), '');
    }

    /** @return Collection<string, string> */
    private function discoverPolicies(): Collection
    {
        if (! $this->disk()->exists('src/Policies')) {
            return collect();
        }

        return collect($this->finder('src/Policies'))
            ->mapWithKeys(fn (SplFileInfo $file): array => $this->policyMap($file));
    }

    private function disk(): Filesystem
    {
        return $this->disk ??= Storage::build([
            'driver' => 'local',
            'root' => $this->path,
        ]);
    }

    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->disk()->path($path))->name('*.php');
    }

    private function join(string $separator, string ...$parts): string
    {
        return implode($separator, array_filter($parts));
    }

    /** @return array<string, string> */
    private function policyMap(SplFileInfo $file): array
    {
        $policyClass = "{$this->namespace}\\Policies\\".$file->getBasename('.php');
        $modelName = str($file->getBasename('.php'))->replaceEnd('Policy', '')->toString();

        return ["{$this->namespace}\\Models\\{$modelName}" => $policyClass];
    }

    private function callProtected(string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($this->provider, $method))->invoke($this->provider, ...$args);
    }
}
