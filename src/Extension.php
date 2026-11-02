<?php

namespace Edalzell\Features;

use Closure;

/**
 * A hook for autoloading something Laravel itself doesn't know about — Livewire
 * components, Statamic addons. Tag a subclass with `Extension::TAG` and every
 * feature hands itself over once its own Laravel pieces have booted. Define only
 * what's needed: both methods default to doing nothing.
 */
abstract class Extension
{
    public const TAG = 'features.extension';

    public function boot(Features $features): void {}

    /**
     * Route groups for every feature, keyed by route filename without extension.
     * They sit above the package defaults and below a feature's own `routeGroups()`.
     * The last extension tagged wins a key.
     *
     * @return array<string, array<string, mixed>|Closure|class-string|null>
     */
    public function routeGroups(): array
    {
        return [];
    }
}
