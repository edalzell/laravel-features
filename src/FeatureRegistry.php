<?php

namespace Edalzell\Features;

use Illuminate\Support\Collection;

class FeatureRegistry
{
    /** @var array<string, Feature> */
    private array $features = [];

    public function add(Feature $feature): void
    {
        $this->features[$feature->name] = $feature;
    }

    /** @return Collection<string, Feature> */
    public function all(): Collection
    {
        return collect($this->features);
    }

    public function get(string $name): ?Feature
    {
        return $this->features[$name] ?? null;
    }
}
