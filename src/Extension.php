<?php

namespace Edalzell\Features;

use Closure;

/**
 * Autoloads what Laravel itself doesn't know about (Livewire components, say).
 * Tag a subclass with `Extension::TAG`; every feature hands itself over once its
 * Laravel pieces have booted. Override only what's needed.
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
