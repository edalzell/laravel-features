<?php

namespace SilentZ\Features\Concerns\Features;

use SilentZ\Features\FeatureServiceProvider;

/**
 * @method bool exists(string $path)
 * @method string path(string $path)
 *
 * @property FeatureServiceProvider $provider
 * @property string $slug
 */
trait HasRoutes
{
    public function registerRoutes(): static
    {
        if ($this->exists('routes')) {
            $this->provider->loadRoutes($this->disk->path('routes'));
        }

        return $this;
    }
}
