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
        return $this->exists('config');
    }

    public function configPath(): string
    {
        return $this->disk->path('config/'.$this->slug);
    }

    public function configTag(): string
    {
        return $this->slug.'-config';
    }
}
