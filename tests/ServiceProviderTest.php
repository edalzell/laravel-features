<?php

use Edalzell\Features\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

it('registers feature', function () {
    $featuresDisk = tap(mockOnDemandDisk('features'))->put('TwoWords/src/ServiceProvider.php', '');

    File::expects('exists')->with(base_path('features'))->andReturns(true);

    $app = mock(Application::class);

    $app
        ->shouldReceive('register')
        ->once()
        ->with('Features\\TwoWords\\ServiceProvider');

    (new ServiceProvider($app))->register();
});

it('doesnt register feature when no provider', function () {
    $featuresDisk = tap(mockOnDemandDisk('features'))->put('TwoWords/src/Foo.php', '');

    File::expects('exists')->with(base_path('features'))->andReturns(true);

    $app = mock(Application::class);

    $app->shouldNotReceive('register');

    (new ServiceProvider($app))->register();
});
