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
        $this->features[$this->key($feature->rootPath)] = $feature;
    }

    /** @return Collection<string, Feature> */
    public function all(): Collection
    {
        return collect($this->features);
    }

    /**
     * The classes every feature has under a directory of `src/`, in registration order.
     *
     * @param  class-string|null  $base
     * @return Collection<int, class-string>
     */
    public function classes(string $directory, ?string $base = null): Collection
    {
        return $this->all()->flatMap->classes($directory, $base);
    }

    public function get(string $rootPath): ?Feature
    {
        return $this->features[$this->key($rootPath)] ?? null;
    }

    public function register(Feature $feature): void
    {
        $this->add($feature);

        $this->app->register($feature->namespace().'\\ServiceProvider');
    }

    /**
     * A path arrives spelled two ways: `dirname()` of a provider's file uses the
     * platform separator, a directory listing may mix it with `/`.
     */
    private function key(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
