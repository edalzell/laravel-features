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
    public function hasMigrations(): bool
    {
        return $this->exists('database/migrations');
    }

    public function migrationsPath(): string
    {
        return $this->disk->path('database/migrations');
    }
}
