<?php

use Edalzell\Features\FeatureServiceProvider;

it('merges config when it exists', function () {
    $disk = tap(mockOnDemandDisk('Features/TwoWords'))->put('config/two-words.php', '');

    $provider = mock(ServiceProvider::class, [mock()])
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/two-words.php'), 'two-words');

    $provider->registerConfig();
});

it('wont merge config when it doesnt exist', function () {
    $disk = mockOnDemandDisk('Features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    $provider->shouldNotReceive('mergeConfigFrom');

    $provider->registerConfig();
});

it('can load migrations', function () {
    $disk = tap(mockOnDemandDisk('Features/TwoWords'))->put('database/migrations/add_table.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $provider->registerMigrations();
});

it('wont load migrations if there arent any', function () {
    $disk = mockOnDemandDisk('Features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadMigrationsFrom');

    $provider->registerMigrations();
});

it('can load routes', function () {
    $disk = tap(mockOnDemandDisk('Features/TwoWords'))->put(DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->with($disk->path(DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php'));

    $provider->registerRoutes();
});

it('wont load routes if there arent any', function () {
    $disk = mockOnDemandDisk('Features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadRoutesFrom');

    $provider->registerRoutes();
});

it('can load views', function () {
    $disk = tap(mockOnDemandDisk('Features/TwoWords'))->put('resources/views/foo.blade.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $provider->registerViews();
});

it('wont load views when there arent any', function () {
    $disk = mockOnDemandDisk('Features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadViewsFrom');

    $provider->registerViews();
});

class ServiceProvider extends FeatureServiceProvider
{
    protected function name(): string
    {
        return 'TwoWords';
    }
}
