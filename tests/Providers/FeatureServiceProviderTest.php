<?php

use Edalzell\Features\Providers\FeatureServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

it('merges config when it exists', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/two-words.php'), 'two-words');

    $provider->register();
});

it('wont merge config when it doesnt exist', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider->shouldNotReceive('mergeConfigFrom');

    $provider->registerConfig();
});

it('publishes config', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('publishes')
        ->once()
        ->with([$disk->path('config/two-words.php') => config_path('two-words.php')], 'two-words-config');

    $provider->boot();
});

it('doesnt publish config when not running in console', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('runningInConsole')->andReturn(false)
        ->getMock();

    $provider = mockServiceProvider(TestServiceProvider::class, $app);
    $provider->shouldNotReceive('slug');
    $provider->boot();
});

it('doesnt publish config when no config', function () {
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('slug')->andReturn('two-words')
        ->shouldNotReceive('publishes');

    $provider->boot();
});

it('can load migrations', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('database/migrations/add_table.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $provider->registerMigrations();
});

it('wont load migrations if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider->shouldNotReceive('loadMigrationsFrom');

    $provider->registerMigrations();
});

it('can load routes', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $provider->registerRoutes();
});

it('wont load routes if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider->shouldNotReceive('loadRoutesFrom');

    $provider->registerRoutes();
});

it('can load views', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('resources/views/foo.blade.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $provider->registerViews();
});

it('wont load views when there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider->shouldNotReceive('loadViewsFrom');

    $provider->registerViews();
});

it('wont register listeners if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    Event::partialMock()->shouldNotReceive('listen');

    $provider->bootListeners();
});

it('wont register policies if there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    Gate::partialMock()->shouldNotReceive('policy');

    $provider->bootPolicies();
});

it('can register policies', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('src/Policies/FooPolicy.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);
    $provider->shouldReceive('namespace')->andReturn('Features\TwoWords');

    Gate::shouldReceive('policy')
        ->once()
        ->with('Features\TwoWords\Models\Foo', 'Features\TwoWords\Policies\FooPolicy');

    $provider->bootPolicies();
});

it('merges config when it exists in group directory', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/admin/two-words.php', '');
    $provider = mockServiceProvider(TestGroupedServiceProvider::class);

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/admin/two-words.php'), 'two-words');

    $provider->register();
});

it('wont merge config when group directory config does not exist', function () {
    tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestGroupedServiceProvider::class);

    $provider->shouldNotReceive('mergeConfigFrom');

    $provider->registerConfig();
});

it('publishes config to group directory when group is set', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/admin/two-words.php', '');
    $provider = mockServiceProvider(TestGroupedServiceProvider::class);

    $provider
        ->shouldReceive('publishes')
        ->once()
        ->with(
            [$disk->path('config/admin/two-words.php') => config_path('admin/two-words.php')],
            'admin-two-words-config'
        );

    $provider->boot();
});

class TestGroupedServiceProvider extends FeatureServiceProvider
{
    protected ?string $group = 'admin';

    protected function name(): string
    {
        return 'TwoWords';
    }
}
