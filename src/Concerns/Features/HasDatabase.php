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
    public function hasDatabase(): bool
    {
        return $this->exists('database');
    }

    public function migrationPath(): string
    {
        return $this->disk->path('database/migrations');
    }
}
