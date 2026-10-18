<?php

namespace Edalzell\Features\Providers;

use Closure;
use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;
use Edalzell\Features\Features;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use ReflectionClass;
use RuntimeException;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    private Features $features;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->features = (new Features($this))
            ->path($this->featuresPath())
            ->name($this->name())
            ->configFileName($this->configFileName())
            ->configGroup($this->configGroup())
            ->configPublishHandle($this->configPublishHandle())
            ->options($this->options())
            ->publishesConfig($this->publishesConfig());
    }

    /**
     * Route groups are read here, not in the constructor, so a package that
     * registers after this one can still set them in config for every feature.
     */
    public function boot(): void
    {
        $this->features->routeGroups($this->routeGroups())->bootFeature();
    }

    public function register(): void
    {
        $this->features->registerFeature();
    }

    protected function configFileName(): string
    {
        return $this->slug();
    }

    protected function configGroup(): string
    {
        return $this->feature()->configGroup ?? '';
    }

    protected function configPublishHandle(): string
    {
        return $this->slug();
    }

    protected function featuresPath(): string
    {
        return $this->directory();
    }

    protected function name(): string
    {
        return basename($this->directory());
    }

    /**
     * Settings for extensions, keyed by dotted name — `livewire.namespace`, say.
     * The core ignores them; each extension reads its own.
     *
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [];
    }

    /**
     * Whether the feature's config is offered to `vendor:publish`. A host package
     * registering its features with `registerFeatures(publishesConfig: false)` keeps
     * the merged defaults but leaves the app's copy to its own config file.
     */
    protected function publishesConfig(): bool
    {
        return $this->feature()->publishesConfig ?? true;
    }

    /**
     * Keyed by route filename without extension. Matches how the framework groups an
     * application's own route files, so `routes/web.php` and `routes/api.php` behave
     * the same inside a feature. Override this to change a group for one feature, or
     * set `features.route_groups` to change it for all of them. The package defaults
     * always sit underneath — another package setting one key in config can't drop
     * the rest — so an entry is `null` to opt a file out, which gets it no middleware
     * group and no prefix, only what the file declares. A closure entry, or an
     * invokable class name, is handed the file's path and the `Features` instance
     * and loads it itself.
     *
     * @return array<string, array<string, mixed>|Closure|class-string|null>
     */
    protected function routeGroups(): array
    {
        return [...$this->defaultRouteGroups(), ...config('features.route_groups', [])];
    }

    protected function slug(): string
    {
        return str($this->name())->kebab()->toString();
    }

    /**
     * The feature's root directory, derived from this provider's own location
     * (`<feature>/src/ServiceProvider.php`) so a feature works wherever it
     * lives — the app, a package, or a directory outside the app entirely.
     */
    /** @return array<string, array<string, mixed>> */
    private function defaultRouteGroups(): array
    {
        return (require dirname(__DIR__, 2).'/config/features.php')['route_groups'];
    }

    private function directory(): string
    {
        $file = (new ReflectionClass(static::class))->getFileName();

        if ($file === false) {
            throw new RuntimeException('Unable to determine the feature directory for ['.static::class.'].');
        }

        return dirname($file, 2);
    }

    private function feature(): ?Feature
    {
        if (! $this->app->bound(FeatureRegistry::class)) {
            return null;
        }

        return $this->app->make(FeatureRegistry::class)->get($this->featuresPath());
    }
}
