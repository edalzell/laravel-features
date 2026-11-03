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
     * Route groups for every feature, keyed by route filename, merged over the
     * defaults and under each feature's own. Last extension tagged wins a key.
     *
     *     return [
     *         'api' => ['middleware' => 'api', 'prefix' => 'api/v1'], // change a group
     *         'cp' => CpRoutes::class,                                // load it yourself
     *     ];
     *
     * @return array<string, array<string, mixed>|Closure|class-string|null>
     */
    public function routeGroups(): array
    {
        return [];
    }
}
