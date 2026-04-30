<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;

it('merges config when it exists', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/two-words.php'), 'two-words');

    $provider->register();
});

it('wont merge config when it doesnt exist', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider();

    $provider->shouldNotReceive('mergeConfigFrom');

    $provider->registerConfig();
});

it('publishes config', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('publishes')
        ->once()
        ->with(['config/two-words.php' => config_path('two-words.php')], 'two-words-config');

    $provider->boot();
});

it('doesnt publish config when not running in console', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('runningInConsole')->andReturn(false)
        ->getMock();

    $provider = mockServiceProvider($app);
    $provider->shouldNotReceive('slug');
    $provider->boot();
});

it('doesnt publish config when no config', function () {
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('slug')->andReturn('two-words')
        ->shouldNotReceive('publishes');

    $provider->boot();
});

it('can load migrations', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('database/migrations/add_table.php', '');
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $provider->registerMigrations();
});

it('wont load migrations if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider();

    $provider->shouldNotReceive('loadMigrationsFrom');

    $provider->registerMigrations();
});

it('can load routes', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $provider->registerRoutes();
});

it('wont load routes if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider();

    $provider->shouldNotReceive('loadRoutesFrom');

    $provider->registerRoutes();
});

it('can load views', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('resources/views/foo.blade.php', '');
    $provider = mockServiceProvider();

    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $provider->registerViews();
});

it('wont load views when there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider();

    $provider->shouldNotReceive('loadViewsFrom');

    $provider->registerViews();
});

it('wont register listeners if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider();

    Event::partialMock()->shouldNotReceive('listen');

    $provider->bootListeners();
});
