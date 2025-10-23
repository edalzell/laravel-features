<?php

namespace Edalzell\Features\Tests;

use Edalzell\Features\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class MakeTestCase extends Orchestra
{
    protected function getApplicationProviders($app)
    {
        $providers = parent::getApplicationProviders($app);

        // Remove production provider
        return array_filter(
            $providers,
            fn ($provider) => $provider !== ServiceProvider::class
        );
    }

    protected function getPackageProviders($app)
    {
        return [MakeServiceProvider::class];
    }
}
