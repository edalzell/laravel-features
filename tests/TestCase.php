<?php

namespace Edalzell\Features\Tests;

use Edalzell\Features\ServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }
}
