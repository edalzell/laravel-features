<?php

namespace Edalzell\Features\Concerns\Features;

use Edalzell\Features\FeatureServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @method bool exists(string $path)
 * @method string path(string $path)
 *
 * @property FeatureServiceProvider $provider
 * @property string $slug
 */
trait HasRoutes
{
    public function registerRoutes(): static
    {
        if (! $this->exists('routes')) {
            return $this;
        }

        collect($this->finder('routes'))
            ->map(fn (SplFileInfo $file) => $file->getRealPath())
            ->filter()
            ->each(fn (string $routePath) => $this->provider->loadRoutes($routePath));

        return $this;
    }

    private function finder(string $path): Finder
    {
        return tap(new Finder)
            ->files()
            ->in($this->path($path))->name('*.php');
    }
}
