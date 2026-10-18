<?php

namespace Edalzell\Features\Extensions;

use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\Features;
use Livewire\Livewire;

/**
 * Livewire only resolves components from the app's own locations, so a feature
 * has to point it at its own. A feature only says where they live: `src/Livewire`
 * for class components, `resources/views` for the single- and multi-file ones,
 * which keep Livewire's own split — `livewire/` for components, `pages/` for full
 * pages — as a dotted prefix: `my-great-feature::livewire.post-list`,
 * `my-great-feature::pages.show`. Livewire allows a namespace one directory, so
 * the views root is the only way both are reachable under one name. Nothing is
 * read until a component is used.
 *
 * The `livewire.namespace` option is prefixed to every component name so two
 * features can each have a `PostList`; an empty string registers a plain
 * location instead, so components answer to their bare names. The binding is only
 * there once Livewire's provider has run, so without Livewire this is a no-op.
 */
class LivewireComponents implements Extension
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
