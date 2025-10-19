<?php

namespace SilentZ\Features\Concerns\Features;

use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * @method bool exists(string $path)
 *
 * @property Filesystem $disk
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
