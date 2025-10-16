<?php

namespace SilentZ\Features\Concerns\Provider;

use SilentZ\Features\Feature;

/**
 * @method bool exists(string $path)
 * @method Filesystem disk()
 * @method Feature feature()
 */
trait SupportsDatabase
{
    public function registerMigrations(Feature $feature): static
    {
        if ($feature->hasMigrations()) {
            $this->loadMigrationsFrom($this->feature()->migrationsPath());
        }

        return $this;
    }
}
