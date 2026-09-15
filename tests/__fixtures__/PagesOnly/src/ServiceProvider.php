<?php

namespace Edalzell\Features\Tests\Fixtures\PagesOnly;

use Edalzell\Features\Providers\FeatureServiceProvider;

/**
 * A feature whose components are full pages under `resources/views/pages`,
 * Livewire's own home for them, with no `resources/views/livewire` at all.
 */
class ServiceProvider extends FeatureServiceProvider {}
