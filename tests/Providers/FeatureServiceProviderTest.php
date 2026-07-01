<?php

use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\SeedersFacade;
use Illuminate\Foundation\Application;

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
    $provider->shouldNotReceive('publishes');
    $provider->boot();
});

it('doesnt publish config when no config file exists', function () {
    mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider->shouldNotReceive('publishes');

    $provider->boot();
});

it('merges config via register', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/two-words.php'), 'two-words');

    $provider->register();
});

it('publishes config to group directory when group is set', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestGroupedServiceProvider::class);

    $provider
        ->shouldReceive('publishes')
        ->once()
        ->with(
            [$disk->path('config/two-words.php') => config_path('admin/two-words.php')],
            'admin-two-words-config'
        );

    $provider->boot();
});

it('merges config from group directory via register', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');
    $provider = mockServiceProvider(TestGroupedServiceProvider::class);

    $provider
        ->shouldReceive('mergeConfigFrom')
        ->once()
        ->with($disk->path('config/admin/two-words.php'), 'two-words');

    $provider->register();
});

it('loads migrations via register', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('database/migrations/create_foo_table.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $provider->register();
});

it('loads routes via register', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $provider->register();
});

it('loads views via register', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('resources/views/show.blade.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $provider->register();
});

it('adds seeders via boot', function () {
    mockOnDemandDisk('features/TwoWords');
    $provider = mockServiceProvider(TestServiceProvider::class);

    SeedersFacade::shouldReceive('add')->once()->with([]);

    $provider->boot();
});

class TestGroupedServiceProvider extends FeatureServiceProvider
{
    protected function configGroup(): string
    {
        return 'admin';
    }

    protected function configPublishHandle(): string
    {
        return 'admin-two-words';
    }

    protected function name(): string
    {
        return 'TwoWords';
    }
}
