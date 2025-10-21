<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

it('can get feature', function () {
    $disk = mockOnDemandDisk('Features/laravel-features');

    expect((new ServiceProvider(mock()))->feature())
        ->toBeInstanceOf(Feature::class)
        ->slug->toBe('laravel-features');
});

it('can load migrations', function () {
    $disk = mockOnDemandDisk('Features/laravel-features');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with('database/migrations');

    $provider->loadMigrations('database/migrations');
});

it('can load routes', function () {
    $disk = mockOnDemandDisk('Features/laravel-features');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->with('routes');

    $provider->loadRoutes('routes');
});

it('can load views', function () {
    $disk = mockOnDemandDisk('Features/laravel-features');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with('resources/views', 'laravel-features');

    $provider->loadViews('resources/views', 'laravel-features');
});

class ServiceProvider extends FeatureServiceProvider {}
