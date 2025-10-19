<?php

use Edalzell\Features\Feature;

it('checks for config file', function () {
    $localDisk = tap(mockOnDemandDisk('Features/One'))->put('config/one.php', '');

    expect(new Feature('One'))
        ->hasConfig()->toBeTrue()
        ->absoluteConfigPath()->toBe($localDisk->path('config/one.php'))
        ->configFile()->toBe('one.php')
        ->configTag()->toBe('one-config')
        ->relativeConfigPath()->toBe('config/one.php');
});
