<?php

use Edalzell\Features\Feature;

it('checks for migrations', function () {
    $localDisk = tap(mockOnDemandDisk('Features/One'))->put('database/migrations/add_table.php', '');

    expect(new Feature('One'))
        ->hasMigrations()->toBeTrue()
        ->migrationsPath()->toBe($localDisk->path('database/migrations'));
});
