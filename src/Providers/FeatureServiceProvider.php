<?php

namespace Edalzell\Features\Providers;

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
            ->routeGroups($this->routeGroups());
    }

    public function boot(): void
    {
        $this->features->bootFeature();
    }

    public function register(): void
    {
        $this->features->registerFeature();
    }

    protected function configFileName(): string
    {
        return $this->slug();
    }

    /**
     * Keyed by route filename without extension. Matches how the framework groups an
     * application's own route files, so `routes/web.php` and `routes/api.php` behave
     * the same inside a feature. Override this to change a group for one feature, or
     * set `features.route_groups` to change it for all of them. A filename with no
     * entry gets no middleware group and no prefix — only what the file declares.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function routeGroups(): array
    {
        return config('features.route_groups', [
            'web' => ['middleware' => 'web'],
            'api' => ['middleware' => 'api', 'prefix' => 'api'],
        ]);
    }

    protected function configGroup(): string
    {
        return '';
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

    protected function slug(): string
    {
        return str($this->name())->kebab()->toString();
    }
}
