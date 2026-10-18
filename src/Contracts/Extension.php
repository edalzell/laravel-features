<?php

namespace Edalzell\Features\Contracts;

use Edalzell\Features\Features;

/**
 * A hook for autoloading something Laravel itself doesn't know about — Livewire
 * components, Statamic addons. Tag an implementation with `Extension::TAG` and
 * every feature hands itself over once its own Laravel pieces have booted.
 */
interface Extension
{
    public const TAG = 'features.extension';

    public function boot(Features $features): void;
}
