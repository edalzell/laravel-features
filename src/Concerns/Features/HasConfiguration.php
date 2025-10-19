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
trait HasConfiguration
{
    public function bootConfig(): static
    {
        if ($this->exists($path = 'config/'.$this->configFile())) {
            $this->provider->publish(
                [$path => config_path($this->configFile())],
                $this->slug.'-config'
            );
        }

        return $this;
    }

    public function registerConfig(): static
    {
        if ($this->exists($path = 'config/'.$this->configFile())) {
            $this->provider->mergeConfig($this->path($path), $this->slug);
        }

        return $this;
    }

    private function configFile(): string
    {
        return $this->slug.'.php';
    }
}
