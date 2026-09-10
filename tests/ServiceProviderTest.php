<?php

use Edalzell\Features\FeatureRegistry;
use Edalzell\Features\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

/**
 * register() merges the package config before discovering features, so a mocked
 * Application has to answer the container lookup that mergeConfigFrom makes.
 */
function mockApplicationWithConfig(): Application
{
    $app = mock(Application::class);

    $app->shouldReceive('singleton')->with(FeatureRegistry::class)->andReturnNull();
    $app->shouldReceive('make')->with(FeatureRegistry::class)->andReturn(new FeatureRegistry);
    $app->shouldReceive('make')->with('config')->andReturn(new Repository);
    $app->shouldReceive('configurationIsCached')->andReturn(false);

    return $app;
}

it('registers feature', function () {
    File::expects('exists')->with(base_path('features'))->andReturns(true);
    File::expects('directories')->with(base_path('features'))->andReturns([base_path('features/TwoWords')]);
    File::expects('exists')->with(base_path('features/TwoWords').'/src/ServiceProvider.php')->andReturns(true);

    $app = mockApplicationWithConfig();

    $app
        ->shouldReceive('register')
        ->once()
        ->with('Features\\TwoWords\\ServiceProvider')
        ->andReturn();

    (new ServiceProvider($app))->register();
});

it('doesnt register feature when no provider', function () {
    File::expects('exists')->with(base_path('features'))->andReturns(true);
    File::expects('directories')->with(base_path('features'))->andReturns([base_path('features/TwoWords')]);
    File::expects('exists')->with(base_path('features/TwoWords').'/src/ServiceProvider.php')->andReturns(false);

    $app = mockApplicationWithConfig();

    $app->shouldNotReceive('register');

    (new ServiceProvider($app))->register();
});
