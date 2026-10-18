<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

class FeatureRegistry
{
    /** @var array<string, Feature> keyed by root path: two packages may each have a `features/Mail` */
    private array $features = [];

    public function __construct(private Application $app) {}

    public function add(Feature $feature): void
    {
        $this->features[$feature->rootPath] = $feature;
    }

    /** @return Collection<string, Feature> */
    public function all(): Collection
    {
        return collect($this->features);
    }

    public function get(string $rootPath): ?Feature
    {
        return $this->features[$rootPath] ?? null;
    }

    public function register(Feature $feature): void
    {
        $this->add($feature);

        $this->app->register($feature->namespace().'\\ServiceProvider');
    }
}
