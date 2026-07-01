<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Features
{
    private readonly Application $app;

    private string $configFileName;

    private string $configGroup = '';

    private string $configPublishHandle;

    private ?Filesystem $disk = null;

    private string $name = '';

    private string $namespace;

    private string $path = '';

    public function __construct(private readonly ServiceProvider $provider)
    {
        $this->app = invade($provider)->app;
        $this->namespace = Str::beforeLast(get_class($provider), '\\');
        $this->configFileName = $this->slug();
        $this->configPublishHandle = $this->slug();
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

    public function bootFeature(): void
    {
        $this
            ->bootConfig()
            ->bootListeners()
            ->bootPolicies()
            ->bootSeeders();
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
        $seeders = $this->discoverSeeders();

        SeedersFacade::add($seeders);

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

    public function path(string $path): static
    {
        $this->path = $path;
        $this->disk = null;

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

    public function registerFeature(): void
    {
        $this
            ->registerConfig()
            ->registerMigrations()
            ->registerRoutes()
            ->registerSeeders()
            ->registerViews();
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

    private function callProtected(string $method, mixed ...$args): mixed
    {
        return invade($this->provider)->{$method}(...$args);
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

    /** @return array<int, string> */
    private function discoverSeeders(): array
    {
        if (! $this->disk()->exists('database/seeders')) {
            return [];
        }

        return collect($this->finder('database/seeders'))
            ->keys()
            ->map(fn (string $path) => $this->getClassNameFromFile($path))
            ->filter(fn (string $class) => is_subclass_of($class, Seeder::class))
            ->all();
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

    private function getClassNameFromFile(string $filePath): ?string
    {
        $tokens = token_get_all(file_get_contents($filePath));
        $namespace = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $i += 2; // skip whitespace
                while (isset($tokens[$i]) && is_array($tokens[$i])) {
                    $namespace .= $tokens[$i][1];
                    $i++;
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                $i += 2; // skip whitespace

                return $namespace ? $namespace.'\\'.$tokens[$i][1] : $tokens[$i][1];
            }
        }

        return null;
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

    private function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }
}
