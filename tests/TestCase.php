<?php

namespace Edalzell\Features\Tests;

use Edalzell\Features\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app)
    {
        return [LivewireServiceProvider::class, ServiceProvider::class];
    }
}
