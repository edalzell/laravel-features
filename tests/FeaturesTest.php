<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

it('merges config when it exists', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    [$features, $provider] = mockFeatures();

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/two-words.php'), 'two-words');

    $features->registerConfig();
});

it('wont merge config when it doesnt exist', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features, $provider] = mockFeatures();

    $provider->shouldNotReceive('mergeConfigFrom');

    $features->registerConfig();
});

it('can load migrations', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('database/migrations/add_table.php', '');
    [$features, $provider] = mockFeatures();

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $features->registerMigrations();
});

it('wont load migrations if there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features, $provider] = mockFeatures();

    $provider->shouldNotReceive('loadMigrationsFrom');

    $features->registerMigrations();
});

it('can load routes', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    [$features, $provider] = mockFeatures();

    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $features->registerRoutes();
});

it('wont load routes if there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features, $provider] = mockFeatures();

    $provider->shouldNotReceive('loadRoutesFrom');

    $features->registerRoutes();
});

it('can load views', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('resources/views/foo.blade.php', '');
    [$features, $provider] = mockFeatures();

    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $features->registerViews();
});

it('wont load views when there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features, $provider] = mockFeatures();

    $provider->shouldNotReceive('loadViewsFrom');

    $features->registerViews();
});

it('wont register listeners if there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features] = mockFeatures();

    Event::partialMock()->shouldNotReceive('listen');

    $features->bootListeners();
});

it('wont register policies if there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features] = mockFeatures();

    Gate::partialMock()->shouldNotReceive('policy');

    $features->bootPolicies();
});

it('can register policies', function () {
    tap(mockOnDemandDisk('features/TwoWords'))->put('src/Policies/FooPolicy.php', '');
    [$features] = mockFeatures();

    Gate::shouldReceive('policy')
        ->once()
        ->with('Features\TwoWords\Models\Foo', 'Features\TwoWords\Policies\FooPolicy');

    $features->bootPolicies();
});

it('merges config when it exists in group directory', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    [$features, $provider] = mockFeatures();
    $features->configGroup('admin');

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/admin/two-words.php'), 'two-words');

    $features->registerConfig();
});

it('wont merge config when group directory config does not exist', function () {
    tap(mockOnDemandDisk('features/TwoWords'))->put('config/foo/two-words.php', '');
    [$features, $provider] = mockFeatures();
    $features->configGroup('admin');

    $provider->shouldNotReceive('mergeConfigFrom');

    $features->registerConfig();
});

it('publishes config when running in console', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    [$features, $provider] = mockFeatures();

    $provider
        ->shouldReceive('publishes')
        ->once()
        ->with([$disk->path('config/two-words.php') => config_path('two-words.php')], 'two-words-config');

    $features->bootConfig();
});

it('doesnt publish config when not running in console', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('runningInConsole')->andReturn(false)
        ->getMock();

    [$features, $provider] = mockFeatures(app: $app);
    $provider->shouldNotReceive('publishes');

    $features->bootConfig();
});
