<?php

namespace SilentZ\Features\Concerns;

/**
 * @method bool exists(string $path)
 * @method Filesystem disk()
 * @method Feature feature()
 */
trait SupportsDatabase
{
    public function registerDatabase(): static
    {
        if ($this->exists('database/migrations')) {
            $this->loadMigrationsFrom($this->feature()->migrationPath());
        }

        return $this;
    }
}
