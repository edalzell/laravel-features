<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

it('registers migrations', function () {
    $localDisk = tap(mockOnDemandDisk('Features/One'))->put('database/migrations/add_table.php', '');
    $provider = mock(new class(mock()) extends FeatureServiceProvider {});

    $provider
        ->shouldReceive('loadMigrations')
        ->once()
        ->with($localDisk->path('database/migrations'));

    (new Feature('One', $provider))->registerDatabase();
});
