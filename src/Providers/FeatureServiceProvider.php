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

    public function boot(): void
    {
        $this->features->routeGroups($this->routeGroups())->bootFeature($this->bootFeature(...));
    }

    public function register(): void
    {
        $this->features->registerFeature();
    }

    /**
     * The feature's own boot code. Runs once the conventions have booted, and
     * after any provider named in `#[BootAfter]`, which `boot()` itself can't
     * wait for.
     */
    protected function bootFeature(): void {}

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
     * Route groups for this feature, keyed by route filename, merged over the
     * defaults and any extension's. Name only what changes.
     *
     *     return [
     *         'api' => ['middleware' => 'api', 'prefix' => 'api/v1'], // change a group
     *         'web' => null,                                          // no group at all
     *         'admin' => AdminRoutes::class,                          // load it yourself
     *     ];
     *
     * @return array<string, array<string, mixed>|Closure|class-string|null>
     */
    protected function routeGroups(): array
    {
        return [];
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
