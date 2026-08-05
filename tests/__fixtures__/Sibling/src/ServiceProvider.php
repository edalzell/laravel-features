<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling;

use Edalzell\Features\Providers\FeatureServiceProvider;

/**
 * A feature that lives outside the app's `features/` directory, autoloaded the
 * same way the composer plugin autoloads one: namespace -> <feature>/src.
 */
class ServiceProvider extends FeatureServiceProvider {}
