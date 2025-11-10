<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

it('loads routes', function () {
    $localDisk = tap(mockOnDemandDisk('Features/One'))->put('routes/web.php', '');
    $provider = mock(new class(mock()) extends FeatureServiceProvider {});

    $provider
        ->shouldReceive('loadRoutes')
        ->once();
        // ->with($localDisk->path('routes/web.php'));

    (new Feature('One', $provider))->registerRoutes();
});
