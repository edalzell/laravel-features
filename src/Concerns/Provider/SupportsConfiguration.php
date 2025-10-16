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
        if ($this->feature()->hasConfig()) {
            $this->publishes([
                $this->disk()->path($this->feature()->configPath()) => config_path($this->feature()->slug),
            ], $this->feature()->configTag());
        }

        return $this;
    }

    public function registerConfiguration(): static
    {
        if ($this->feature()->hasConfig()) {
            $this->mergeConfigFrom($this->feature()->configPath().'.php', $this->feature()->slug);
        }

        return $this;
    }
}
