<?php

use Edalzell\Features\FeatureServiceProvider;
use Illuminate\Support\Facades\Event;

it('merges config when it exists', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('config/two-words.php', '');

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
    $disk = mockOnDemandDisk('features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    $provider->shouldNotReceive('mergeConfigFrom');

    $provider->registerConfig();
});

it('can load migrations', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('database/migrations/add_table.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider
        ->shouldReceive('loadMigrationsFrom')
        ->once()
        ->with($disk->path('database/migrations'));

    $provider->registerMigrations();
});

it('wont load migrations if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadMigrationsFrom');

    $provider->registerMigrations();
});

it('can load routes', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadRoutesFrom')
        ->once()
        ->withArgs(fn (string $path) => tidy($path) === tidy($disk->path('routes/web.php')));

    $provider->registerRoutes();
});

it('wont load routes if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadRoutesFrom');

    $provider->registerRoutes();
});

it('can load views', function () {
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('resources/views/foo.blade.php', '');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();
    $provider
        ->shouldReceive('loadViewsFrom')
        ->once()
        ->with($disk->path('resources/views'), 'two-words');

    $provider->registerViews();
});

it('wont load views when there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->shouldNotReceive('loadViewsFrom');

    $provider->registerViews();
});

it('wont register listeners if there arent any', function () {
    $disk = mockOnDemandDisk('features/TwoWords');

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    Event::partialMock()->shouldNotReceive('listen');

    $provider->bootListeners();
});

it('register listeners', function () {
    Event::fake();
    mockOnDemandDisk('features/TwoWords')->put('src/Listeners/Bar.php', '');

    $this->mock('alias:Illuminate\Foundation\Events\DiscoverEvents')
        ->shouldReceive('guessClassNamesUsing')->andReturn()
        ->shouldReceive('within')
        ->andReturn(['the-event' => [Listener::class]]);

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->bootListeners();

    Event::assertListening('the-event', Listener::class);
});

class ServiceProvider extends FeatureServiceProvider
{
    protected function name(): string
    {
        return 'TwoWords';
    }
}

class Listener
{
    public function handle(): void {}
}
