<?php

use Edalzell\Features\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

it('registers feature', function () {
    File::expects('exists')->with(base_path('features'))->andReturns(true);
    File::expects('directories')->with(base_path('features'))->andReturns([base_path('features/TwoWords')]);
    File::expects('exists')->with(base_path('features/TwoWords').'/src/ServiceProvider.php')->andReturns(true);

    $app = mock(Application::class);

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

    $app = mock(Application::class);

    $app->shouldNotReceive('register');

    (new ServiceProvider($app))->register();
});
