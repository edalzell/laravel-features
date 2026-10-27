<?php

namespace Edalzell\Features;

use Closure;
use Edalzell\Features\Attributes\BootAfter;
use Edalzell\Features\Contracts\Extension;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Features
{
    private readonly Application $app;

    private ?string $configFileName = null;

    private string $configGroup = '';

    private ?string $configPublishHandle = null;

    private string $name;

    private string $namespace;

    /** @var array<string, mixed> */
    private array $options = [];

    private string $path;

    private bool $publishesConfig = true;

    /** @var array<string, array<string, mixed>|Closure|class-string|null> */
    private array $routeGroups = [];

    public function __construct(private readonly ServiceProvider $provider)
    {
        $reflection = new ReflectionClass($provider);

        $this->app = (new ReflectionProperty($provider, 'app'))->getValue($provider);
        $this->path = dirname($reflection->getFileName(), 2);
        $this->namespace = $reflection->getNamespaceName();
        $this->name = basename($this->path);
    }

    public function app(): Application
    {
        return $this->app;
    }

    public function bootCommands(): static
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->classes('Console/Commands', Command::class)
            ->whenNotEmpty(fn (Collection $commands) => $this->provider->commands($commands->all()));

        return $this;
    }

    public function bootConfig(): static
    {
        if (! $this->publishesConfig || ! $this->app->runningInConsole()) {
            return $this;
        }

        if (! $this->has($path = $this->configRelativePath())) {
            return $this;
        }

        $this->callProtected(
            'publishes',
            [$this->pathTo($path) => config_path($this->join('/', $this->configGroup, $this->configFile().'.php'))],
            $this->join('-', $this->configPublishHandle ?? $this->slug(), 'config'),
        );

        return $this;
    }

    /**
     * Last, so an extension sees a feature whose Laravel pieces are all in place.
     * Resolved through the container tag rather than a static list so a package
     * only has to tag itself to take part.
     */
    public function bootExtensions(): static
    {
        collect($this->app->tagged(Extension::TAG))->each->boot($this);

        return $this;
    }

    /**
     * A feature registers from inside its host's `register()`, so it boots
     * before the host and before anything registered later. `#[BootAfter]` on
     * the feature's provider holds its boot until the named provider has booted.
     */
    public function bootFeature(): void
    {
        if (is_null($after = $this->bootsAfter()) || $this->hasBooted($after)) {
            $this->bootAll();

            return;
        }

        $after->booted(fn () => $this->bootAll());
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

    /**
     * Booted, not registered. A feature's route file may reach for a macro another
     * package defines — `Route::livewire()` is the common one — and packages are
     * registered in discovery order, so this package can run long before the one
     * that supplies the macro. Booting happens once every provider has registered,
     * which is also where the framework's own route files are loaded.
     */
    public function bootRoutes(): static
    {
        if ($this->app->routesAreCached() || ! $this->has('routes')) {
            return $this;
        }

        collect($this->finder('routes')->depth(0))
            ->each(fn (SplFileInfo $file) => $this->loadRouteFile($file));

        return $this;
    }

    public function bootSeeders(): static
    {
        SeedersFacade::add(ClassFinder::classes($this->pathTo('database/seeders'), Seeder::class)->all());

        return $this;
    }

    /**
     * The classes under a directory of `src/`. A depth limits how far down to
     * look; a base keeps only concrete classes that extend or implement it.
     * The built-in conventions go through the same scan.
     *
     * @param  class-string|null  $base
     * @return Collection<int, class-string>
     */
    public function classes(string $directory, ?string $base = null, ?int $depth = null): Collection
    {
        return ClassFinder::classes($this->pathTo("src/{$directory}"), $base, $depth);
    }

    /**
     * Offered to `vendor:publish` as `{slug}-views`, landing where Laravel
     * already looks for overrides of the `{slug}::` namespace.
     */
    public function bootViews(): static
    {
        if (! $this->app->runningInConsole() || ! $this->has('resources/views')) {
            return $this;
        }

        $this->callProtected(
            'publishes',
            [$this->pathTo('resources/views') => resource_path("views/vendor/{$this->slug()}")],
            "{$this->slug()}-views",
        );

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

    /** Whether the feature has this file or directory. */
    public function has(string $relative): bool
    {
        return file_exists($this->pathTo($relative));
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->options, $key) ?? $default;
    }

    /**
     * Settings an extension reads per feature, keyed by dotted name
     * (`livewire.namespace`). The core doesn't look at them.
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function pathTo(string $relative = ''): string
    {
        return $relative === '' ? $this->path : "{$this->path}/".ltrim($relative, '/');
    }

    public function provider(): ServiceProvider
    {
        return $this->provider;
    }

    /**
     * The defaults are still merged at `{group}.{file}`; only the copy into the
     * app's `config/` is skipped. A host package uses this when its features'
     * settings belong nested in the package's own config file rather than in a
     * `config/{group}/` directory of their own.
     */
    public function publishesConfig(bool $publishes): static
    {
        $this->publishesConfig = $publishes;

        return $this;
    }

    public function registerConfig(): static
    {
        if (! $this->has($path = $this->configRelativePath())) {
            return $this;
        }

        $this->callProtected('mergeConfigFrom', $this->pathTo($path), $this->configMergeKey());

        return $this;
    }

    public function registerFeature(): void
    {
        $this
            ->registerConfig()
            ->registerMigrations()
            ->registerSeeders()
            ->registerTranslations()
            ->registerViews();
    }

    public function registerMigrations(): static
    {
        if (! $this->has('database/migrations')) {
            return $this;
        }

        $this->callProtected('loadMigrationsFrom', $this->pathTo('database/migrations'));

        return $this;
    }

    public function registerSeeders(): static
    {
        if (! $this->app->bound(Seeders::class)) {
            $this->app->singleton(Seeders::class, fn () => new Seeders);
        }

        return $this;
    }

    public function registerTranslations(): static
    {
        if (! $this->has('lang')) {
            return $this;
        }

        $this->callProtected('loadTranslationsFrom', $this->pathTo('lang'), $this->slug());
        $this->callProtected('loadJsonTranslationsFrom', $this->pathTo('lang'));

        return $this;
    }

    public function registerViews(): static
    {
        if (! $this->has('resources/views')) {
            return $this;
        }

        $this->callProtected('loadViewsFrom', $this->pathTo('resources/views'), $this->slug());

        return $this;
    }

    public function rootNamespace(): string
    {
        return $this->namespace;
    }

    public function rootPath(): string
    {
        return $this->path;
    }

    /**
     * `loadRoutesFrom()` is a bare require, so without this a feature's
     * `routes/web.php` gets no `web` middleware — no session, no CSRF — and
     * `routes/api.php` no `api` middleware and no prefix. The framework puts its own
     * route files in a group; features should behave like the app. A closure, or
     * the name of an invokable class, is handed the file's path and this instance
     * instead and loads it however it likes — the class so config stays cacheable.
     *
     * @param  array<string, array<string, mixed>|Closure|class-string|null>  $groups
     */
    public function routeGroups(array $groups): static
    {
        $this->routeGroups = $groups;

        return $this;
    }

    public function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }

    private function bootAll(): void
    {
        $this
            ->bootCommands()
            ->bootConfig()
            ->bootListeners()
            ->bootPolicies()
            ->bootRoutes()
            ->bootSeeders()
            ->bootViews()
            ->bootExtensions();
    }

    /** Read through the provider's parents too, so a base class can carry the attribute. */
    private function bootAfterAttribute(): ?BootAfter
    {
        return collect([$this->provider::class, ...class_parents($this->provider)])
            ->flatMap(fn (string $class) => (new ReflectionClass($class))->getAttributes(BootAfter::class))
            ->first()
            ?->newInstance();
    }

    private function bootsAfter(): ?ServiceProvider
    {
        if (is_null($attribute = $this->bootAfterAttribute())) {
            return null;
        }

        throw_if(
            is_null($provider = Arr::first($this->app->getProviders($attribute->provider))),
            new LogicException("Feature [{$this->name}] boots after [{$attribute->provider}], which is not registered."),
        );

        return $provider;
    }

    private function callProtected(string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($this->provider, $method))->invoke($this->provider, ...$args);
    }

    /** Defaults to the slug, like the publish handle, unless the feature says otherwise. */
    private function configFile(): string
    {
        return $this->configFileName ?? $this->slug();
    }

    private function configMergeKey(): string
    {
        return $this->join('.', $this->configGroup, $this->configFile());
    }

    private function configRelativePath(): string
    {
        return $this->join('/', 'config', $this->configFile().'.php');
    }

    /** @return array<string, array<string>> */
    private function discoverEvents(): array
    {
        if (! $this->has('src/Listeners')) {
            return [];
        }

        // The callback is a static on DiscoverEvents, so it is cleared again
        // straight after: left in place it would misname the app's own listeners
        // the next time anything discovers events — `event:cache`, say.
        // @phpstan-ignore-next-line argument.type — a name parsed from the file can't be proven a class-string
        DiscoverEvents::guessClassNamesUsing(fn (SplFileInfo $file) => ClassFinder::className($file->getRealPath()) ?? '');

        try {
            return DiscoverEvents::within($this->pathTo('src/Listeners'), '');
        } finally {
            DiscoverEvents::$guessClassNamesUsingCallback = null;
        }
    }

    /** @return Collection<string, string> */
    private function discoverPolicies(): Collection
    {
        return $this->classes('Policies')->mapWithKeys(fn (string $policy): array => $this->policyMap($policy));
    }

    /** Sorted, so what a feature registers doesn't depend on the filesystem's order. */
    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->pathTo($path))
            ->name('*.php')
            ->sortByName();
    }

    /** One registered ahead of the feature's provider has booted by the time the feature does. */
    private function hasBooted(ServiceProvider $provider): bool
    {
        $providerAt = $this->position($provider);
        $featureAt = $this->position($this->provider);

        return ! is_null($providerAt) && ! is_null($featureAt) && $providerAt < $featureAt;
    }

    private function join(string $separator, string ...$parts): string
    {
        return implode($separator, array_filter($parts));
    }

    private function loadRouteFile(SplFileInfo $file): void
    {
        if (! $path = $file->getRealPath()) {
            return;
        }

        $group = $this->routeGroups[$file->getBasename('.php')] ?? null;

        if ($group === null) {
            $this->callProtected('loadRoutesFrom', $path);

            return;
        }

        if ($group instanceof Closure) {
            $group($path, $this);

            return;
        }

        if (is_string($group)) {
            throw_unless(
                method_exists($group, '__invoke'),
                new InvalidArgumentException("Route group [{$group}] for routes/{$file->getBasename()} of feature [{$this->name}] is not an invokable class."),
            );

            $this->app->make($group)($path, $this);

            return;
        }

        Route::group($group, fn () => $this->callProtected('loadRoutesFrom', $path));
    }

    /** @return array<string, string> */
    private function policyMap(string $policy): array
    {
        $model = str(class_basename($policy))->replaceEnd('Policy', '')->toString();

        return ["{$this->namespace}\\Models\\{$model}" => $policy];
    }

    /** Where the provider sits in registration order, which is also boot order. */
    private function position(ServiceProvider $provider): ?int
    {
        return array_key_first($this->app->getProviders($provider));
    }
}
