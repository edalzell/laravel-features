<?php

namespace Edalzell\Features\Extensions;

use Edalzell\Features\Extension;
use Edalzell\Features\Features;
use Livewire\Livewire;

/**
 * Registers a feature's `src/Livewire` and `resources/views` with Livewire under
 * the `livewire.namespace` option (default: the slug). The views root is the
 * namespace's view path so `livewire/` and `pages/` are both reachable under one
 * name. An empty namespace registers a plain location instead.
 */
class LivewireComponents extends Extension
{
    public function boot(Features $features): void
    {
        if (! $features->app()->bound('livewire') || ! method_exists(Livewire::getFacadeRoot(), 'addNamespace')) {
            return;
        }

        $viewPath = $features->pathTo('resources/views');
        $classNamespace = $features->rootNamespace().'\\Livewire';

        if (($namespace = (string) $features->option('livewire.namespace', $features->slug())) === '') {
            Livewire::addLocation(viewPath: $viewPath, classNamespace: $classNamespace);

            return;
        }

        Livewire::addNamespace(
            $namespace,
            viewPath: $viewPath,
            classNamespace: $classNamespace,
            classPath: $features->pathTo('src/Livewire'),
            classViewPath: $features->pathTo('resources/views/livewire'),
        );
    }
}
