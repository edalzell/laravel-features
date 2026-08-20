<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Features
{
    private readonly Application $app;

    private string $configFileName;

    private string $configGroup = '';

    private string $configPublishHandle;

    private ?Filesystem $disk = null;

    private string $livewireNamespace;

    private string $name;

    private string $namespace;

    private string $path;

    /** @var array<string, array<string, mixed>> */
    private array $routeGroups = [];

    public function __construct(private readonly ServiceProvider $provider)
    {
        $reflection = new ReflectionClass($provider);

        $this->app = (new ReflectionProperty($provider, 'app'))->getValue($provider);
        $this->path = dirname($reflection->getFileName(), 2);
        $this->namespace = $reflection->getNamespaceName();
        $this->name = basename($this->path);
        $this->applySlugDefaults();
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
            ->bootLivewireComponents()
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

    /**
     * Livewire's own auto-discovery only knows about the app's own component
     * locations, so a feature has to point Livewire at its own. Livewire 4 takes a
     * namespace and resolves it lazily; Livewire 3 has no such thing, so its
     * components are registered one by one instead. The binding is only there once
     * Livewire's service provider has run, so a feature is a no-op without it.
     */
    public function bootLivewireComponents(): static
    {
        if (! $this->app->bound('livewire')) {
            return $this;
        }

        if ($this->livewireResolvesNamespaces()) {
            return $this->addLivewireLocations();
        }

        $this
            ->discoverLivewireComponents()
            ->each(fn (string $class, string $name) => Livewire::component($name, $class));

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

    /**
     * Prefixed to every Livewire component name in the feature, so two features can
     * each have a `PostList` without clashing. Pass an empty string to drop it.
     */
    public function livewireNamespace(string $namespace): static
    {
        $this->livewireNamespace = $namespace;

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        $this->applySlugDefaults();

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
            ->each(fn (SplFileInfo $file) => $this->loadRouteFile($file));

        return $this;
    }

    /**
     * `loadRoutesFrom()` is a bare require, so without this a feature's
     * `routes/web.php` gets no `web` middleware — no session, no CSRF — and
     * `routes/api.php` no `api` middleware and no prefix. The framework puts its own
     * route files in a group; features should behave like the app.
     *
     * @param  array<string, array<string, mixed>>  $groups
     */
    public function routeGroups(array $groups): static
    {
        $this->routeGroups = $groups;

        return $this;
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

        Route::group($group, fn () => $this->callProtected('loadRoutesFrom', $path));
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

    /**
     * Livewire 4 resolves lazily, so a feature only has to say where its components
     * live: `src/Livewire` for class components, `resources/views/livewire` for the
     * single- and multi-file ones. Livewire names them itself, the way it names the
     * app's own — `my-great-feature::post-list`. A feature that wants no namespace
     * gets a plain location, so its components answer to their bare names.
     */
    private function addLivewireLocations(): static
    {
        $viewPath = $this->disk()->path('resources/views/livewire');

        if ($this->livewireNamespace === '') {
            Livewire::addLocation(viewPath: $viewPath, classNamespace: $this->livewireRootNamespace());

            return $this;
        }

        Livewire::addNamespace(
            $this->livewireNamespace,
            viewPath: $viewPath,
            classNamespace: $this->livewireRootNamespace(),
            classPath: $this->disk()->path('src/Livewire'),
            classViewPath: $viewPath,
        );

        return $this;
    }

    /**
     * The settings that take the feature's slug unless the feature says otherwise.
     * They all follow the name, so they are re-derived whenever it changes.
     */
    private function applySlugDefaults(): void
    {
        $this->configFileName = $this->slug();
        $this->configPublishHandle = $this->slug();
        $this->livewireNamespace = $this->slug();
    }

    private function callProtected(string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($this->provider, $method))->invoke($this->provider, ...$args);
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

    /** @return Collection<string, class-string<Component>> */
    private function discoverLivewireComponents(): Collection
    {
        if (! $this->disk()->exists('src/Livewire')) {
            return collect();
        }

        return collect($this->finder('src/Livewire'))
            ->map(fn (SplFileInfo $file): string => $this->livewireClass($file))
            ->filter(fn (string $class): bool => is_subclass_of($class, Component::class))
            ->mapWithKeys(fn (string $class): array => [$this->livewireName($class) => $class]);
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

    private function livewireClass(SplFileInfo $file): string
    {
        $relative = str($file->getRelativePathname())
            ->replaceEnd('.php', '')
            ->replace('/', '\\');

        return $this->livewireRootNamespace().'\\'.$relative;
    }

    /**
     * Built the same way Livewire builds its own names: every namespace segment below
     * `src/Livewire` kebab-cased and joined with dots, with a trailing `.index`
     * dropped so `Posts\Index` answers to `posts`.
     */
    private function livewireName(string $class): string
    {
        $name = str($class)
            ->after($this->livewireRootNamespace().'\\')
            ->explode('\\')
            ->map(fn (string $segment): string => str($segment)->kebab()->toString())
            ->join('.');

        return $this->join('.', $this->livewireNamespace, str($name)->replaceEnd('.index', '')->toString());
    }

    /** The Finder is Livewire 4's, and it is what resolves a registered namespace. */
    private function livewireResolvesNamespaces(): bool
    {
        return $this->app->bound('livewire.finder');
    }

    private function livewireRootNamespace(): string
    {
        return "{$this->namespace}\\Livewire";
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
