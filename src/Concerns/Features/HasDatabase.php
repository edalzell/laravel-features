<?php

namespace Edalzell\Features\Concerns\Features;

use Edalzell\Features\FeatureServiceProvider;

/**
 * @method bool exists(string $path)
 * @method string path(string $path)
 *
 * @property FeatureServiceProvider $provider
 * @property string $slug
 */
trait HasDatabase
{
    public function registerDatabase(): static
    {
        if ($this->exists('database/migrations')) {
            $this->provider->loadMigrations($this->disk->path('database/migrations'));
        }

        return $this;
    }
}
