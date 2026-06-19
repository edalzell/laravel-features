<?php

namespace Edalzell\Features\Providers;

use Edalzell\Features\Features;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use ReflectionClass;

abstract class FeatureServiceProvider extends LaravelServiceProvider
{
    /** @var array<int, string> */
    protected array $seeders = [];

    protected string $name;

    /** @var ReflectionClass<static> */
    protected ReflectionClass $reflection;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->reflection = new ReflectionClass(static::class);
        $this->name = $this->name();
    }

    public function boot(): void
    {
        $this->makeFeatures()->bootFeature();
    }

    public function register(): void
    {
        $this->makeFeatures()->registerFeature();
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
        return base_path('features/'.$this->name);
    }

    protected function name(): string
    {
        return basename(dirname($this->reflection->getFileName(), 2));
    }

    protected function slug(): string
    {
        return str($this->name)->kebab()->toString();
    }

    private function makeFeatures(): Features
    {
        return (new Features($this))
            ->path($this->featuresPath())
            ->name($this->name)
            ->configFileName($this->configFileName())
            ->configGroup($this->configGroup())
            ->configPublishHandle($this->configPublishHandle())
            ->seeders($this->seeders);
    }
}
