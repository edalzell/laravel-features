<?php

namespace SilentZ\Features\Concerns\Provider;

use Illuminate\Contracts\Filesystem\Filesystem;
use SilentZ\Features\Feature;

/**
 * @method bool exists(string $path)
 * @method Filesystem disk()
 * @method Feature feature()
 */
trait SupportsConfiguration
{
    public function bootConfiguration(): static
    {
        $feature = $this->feature();

        if ($feature->hasConfig()) {
            $this->publishes([
                $feature->absoluteConfigPath() => config_path($feature->configFile()),
            ], $feature->configTag());
        }

        return $this;
    }

    public function registerConfiguration(): static
    {
        if ($this->feature()->hasConfig()) {
            $this->mergeConfigFrom($this->feature()->absoluteConfigPath(), $this->feature()->slug);
        }

        return $this;
    }
}
