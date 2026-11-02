<?php

use Edalzell\Features\Extension;
use Edalzell\Features\Extensions\LivewireComponents;
use Edalzell\Features\FeatureRegistry;
use Edalzell\Features\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

function mockApplicationForRegister(): Application
{
    $app = mock(Application::class);

    $app->shouldReceive('singleton')->with(FeatureRegistry::class)->andReturnNull();
    $app->shouldReceive('singletonIf')->with(FeatureRegistry::class)->andReturnNull();
    $app->shouldReceive('make')->with(FeatureRegistry::class)->andReturn(new FeatureRegistry($app));
    $app->shouldReceive('tag')->with(LivewireComponents::class, Extension::TAG)->byDefault();

    return $app;
}

it('registers feature', function () {
    File::expects('exists')->with(base_path('features'))->andReturns(true);
    File::expects('directories')->with(base_path('features'))->andReturns([base_path('features/TwoWords')]);
    File::expects('exists')->with(base_path('features/TwoWords').'/src/ServiceProvider.php')->andReturns(true);

    $app = mockApplicationForRegister();

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

    $app = mockApplicationForRegister();

    $app->shouldNotReceive('register');

    (new ServiceProvider($app))->register();
});

it('tags the livewire extension', function () {
    $app = mockApplicationForRegister();

    File::expects('exists')->with(base_path('features'))->andReturns(false);
    $app->shouldReceive('tag')->once()->with(LivewireComponents::class, Extension::TAG);

    (new ServiceProvider($app))->register();
});
