<?php

namespace Edalzell\Features;

/**
 * A hook for autoloading something Laravel itself doesn't know about — Livewire
 * components, Statamic addons. Tag a subclass with `Extension::TAG` and every
 * feature hands itself over once its own Laravel pieces have booted.
 */
abstract class Extension
{
    public const TAG = 'features.extension';

    public function boot(Features $features): void {}
}
