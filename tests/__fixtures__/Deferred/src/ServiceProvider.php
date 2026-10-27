<?php

namespace Edalzell\Features\Tests\Fixtures\Deferred;

use Edalzell\Features\Attributes\BootAfter;
use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\Tests\Fixtures\DeferredHost;

#[BootAfter(DeferredHost::class)]
class ServiceProvider extends FeatureServiceProvider {}
