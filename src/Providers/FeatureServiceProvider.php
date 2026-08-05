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
            ->configPublishHandle($this->configPublishHandle());
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
