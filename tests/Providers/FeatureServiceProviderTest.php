<?php

use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\SeedersFacade;
use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
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

it('loads routes via boot', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $provider->boot();
});

it('doesnt load routes via register', function () {
    tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    $provider = mockServiceProvider(TestServiceProvider::class);

    // Registering is too early: a route file may call a macro — `Route::livewire()`
    // — that another package only defines when its own provider registers, and
    // discovery order can put this package first.
    $provider->shouldNotReceive('loadRoutesFrom');

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

it('loads migrations for a feature outside the app features directory', function () {
    (new SiblingServiceProvider(app()))->register();

    expect(array_map(tidy(...), app('migrator')->paths()))
        ->toContain(tidy(fixturePath('Sibling/database/migrations')));
});

it('derives the feature name and slug from the provider location', function () {
    (new SiblingServiceProvider(app()))->register();

    $hints = view()->getFinder()->getHints();

    expect($hints)
        ->toHaveKey('sibling')
        ->and(array_map(tidy(...), $hints['sibling']))
        ->toContain(tidy(fixturePath('Sibling/resources/views')));
});

class TestGroupedServiceProvider extends FeatureServiceProvider
{
    protected function featuresPath(): string
    {
        return base_path('features/TwoWords');
    }

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
