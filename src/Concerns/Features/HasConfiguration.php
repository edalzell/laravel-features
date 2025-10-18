<?php

namespace SilentZ\Features\Concerns\Features;

use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * @method bool exists(string $path)
 *
 * @property Filesystem $disk
 * @property string $slug
 */
trait HasConfiguration
{
    public function hasConfig(): bool
    {
        return $this->exists($this->relativeConfigPath());
    }

    public function absoluteConfigPath(): string
    {
        return $this->disk()->path('config/'.$this->configFile());
    }

    public function configFile(): string
    {
        return $this->slug.'.php';
    }

    public function configTag(): string
    {
        return $this->slug.'-config';
    }

    public function relativeConfigPath(): string
    {
        return 'config/'.$this->configFile();
    }
}
