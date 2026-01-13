<?php

use Edalzell\Features\Facade;
use Illuminate\Contracts\Foundation\Application;

it('registers feature', function () {
    $featuresDisk = tap(mockOnDemandDisk('Features'))->put('TwoWords/src/ServiceProvider.php', '');

    File::expects('exists')->with(app_path('Features'))->andReturns(true);

    $app = mock(Application::class);

    $app
        ->shouldReceive('register')
        ->once()
        ->with('App\\Features\\TwoWords\\ServiceProvider');

    Facade::register($app);
});

it('doesnt register feature when no provider', function () {
    $featuresDisk = tap(mockOnDemandDisk('Features'))->put('TwoWords/src/Foo.php', '');

    File::expects('exists')->with(app_path('Features'))->andReturns(true);

    $app = mock(Application::class);

    $app->shouldNotReceive('register');

    Facade::register($app);
});
