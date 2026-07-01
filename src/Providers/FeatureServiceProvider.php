<?php

namespace Edalzell\Features\Providers;

use Edalzell\Features\Features;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

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
        return base_path('features/'.$this->name());
    }

    protected function name(): string
    {
        $parts = explode('\\', static::class);

        return $parts[array_key_last($parts) - 1];
    }

    protected function slug(): string
    {
        return str($this->name())->kebab()->toString();
    }
}
