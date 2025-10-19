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
trait HasViews
{
    public function registerViews(): static
    {
        if ($this->exists('resources/views')) {
            $this->provider->loadViews($this->disk->path('views'), $this->slug);
        }

        return $this;
    }
}
