<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

it('loads routes', function () {
    $localDisk = tap(mockOnDemandDisk('Features/One'))->put('resources/views/test.blade.php', '');
    $provider = mock(new class(mock()) extends FeatureServiceProvider {});

    $provider
        ->shouldReceive('loadViews')
        ->once()
        ->with($localDisk->path('resources/views'), 'one');

    (new Feature('One', $provider))->registerViews();
});
