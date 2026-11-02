<?php

use Edalzell\Features\Extension;
use Edalzell\Features\Features;
use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

function siblingRoute(string $name): ?Illuminate\Routing\Route
{
    return collect(Route::getRoutes()->getRoutes())
        ->first(fn (Illuminate\Routing\Route $route) => $route->getName() === $name);
}

/** @param  array<string, array<string, mixed>|Closure|class-string|null>  $groups */
function siblingWith(array $groups): GroupedSiblingServiceProvider
{
    return new GroupedSiblingServiceProvider(app(), $groups);
}

/** @param  array<string, array<string, mixed>|Closure|class-string|null>  $groups */
function tagRouteGroups(array $groups, string $abstract = 'extension'): void
{
    app()->instance($abstract, new RouteGroupsExtension($groups));
    app()->tag($abstract, Extension::TAG);
}

it('puts routes/web.php in the web middleware group', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(siblingRoute('sibling.web')?->gatherMiddleware())->toContain('web');
});

it('puts routes/api.php in the api group and prefixes it', function () {
    (new SiblingServiceProvider(app()))->boot();

    $route = siblingRoute('sibling.api');

    expect($route?->uri())->toBe('api/sibling-api')
        ->and($route?->gatherMiddleware())->toContain('api');
});

it('lets a feature override its own groups', function () {
    siblingWith(['api' => ['prefix' => 'internal']])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('internal/sibling-api');
});

it('keeps the other groups when a feature overrides one', function () {
    siblingWith(['api' => ['prefix' => 'internal']])->boot();

    expect(siblingRoute('sibling.web')?->gatherMiddleware())->toContain('web');
});

it('adds no middleware group or prefix when the entry is null', function () {
    siblingWith(['api' => null])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('sibling-api')
        ->and(siblingRoute('sibling.api')?->gatherMiddleware())->not->toContain('api');
});

it('keeps the default groups when a feature only names another', function () {
    siblingWith(['admin' => ['prefix' => 'admin']])->boot();

    expect(siblingRoute('sibling.web')?->gatherMiddleware())->toContain('web')
        ->and(siblingRoute('sibling.api')?->uri())->toBe('api/sibling-api');
});

it('rejects a string group that is not an invokable class', function () {
    siblingWith(['api' => 'api'])->boot();
})->throws(InvalidArgumentException::class, 'Route group [api] for routes/api.php of feature [Sibling]');

it('lets an extension change a group for every feature', function () {
    tagRouteGroups(['api' => PrefixedLoader::class]);

    (new SiblingServiceProvider(app()))->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('loader/sibling-api');
});

it('keeps the default groups when an extension only names another', function () {
    tagRouteGroups(['admin' => ['prefix' => 'admin']]);

    (new SiblingServiceProvider(app()))->boot();

    expect(siblingRoute('sibling.web')?->gatherMiddleware())->toContain('web')
        ->and(siblingRoute('sibling.api')?->uri())->toBe('api/sibling-api');
});

it('lets a feature override one of an extension\'s groups and keep the rest', function () {
    tagRouteGroups(['api' => PrefixedLoader::class, 'web' => ['prefix' => 'extension']]);

    siblingWith(['api' => ['prefix' => 'internal']])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('internal/sibling-api')
        ->and(siblingRoute('sibling.web')?->uri())->toBe('extension/sibling-web');
});

it('lets a feature opt a file out of an extension\'s group', function () {
    tagRouteGroups(['api' => PrefixedLoader::class]);

    siblingWith(['api' => null])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('sibling-api');
});

it('lets the last tagged extension win a group', function () {
    tagRouteGroups(['api' => ['prefix' => 'first']], 'first');
    tagRouteGroups(['api' => ['prefix' => 'second']], 'second');

    (new SiblingServiceProvider(app()))->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('second/sibling-api');
});

it('loads nothing when the routes are cached', function () {
    $app = mock(Application::class)->makePartial()->shouldReceive('routesAreCached')->andReturn(true)->getMock();
    $disk = tap(mockOnDemandDisk('features/TwoWords'))->put('routes/web.php', '');
    [$features, $provider] = mockFeatures(app: $app);

    $provider->shouldNotReceive('loadRoutesFrom');

    $features->bootRoutes();
});

it('loads route files in name order, top level only', function () {
    $disk = mockOnDemandDisk('features/TwoWords');
    $disk->put('routes/zeta.php', '');
    $disk->put('routes/alpha.php', '');
    $disk->put('routes/nested/beta.php', '');
    [$features, $provider] = mockFeatures();

    $provider->shouldReceive('loadRoutesFrom')->once()->ordered()->withArgs(fn (string $path) => str_ends_with($path, 'alpha.php'));
    $provider->shouldReceive('loadRoutesFrom')->once()->ordered()->withArgs(fn (string $path) => str_ends_with($path, 'zeta.php'));

    $features->bootRoutes();
});

it('hands the route file to a closure instead of grouping it', function () {
    siblingWith(['api' => fn (string $path) => Route::prefix('closure')->group($path)])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('closure/sibling-api')
        ->and(siblingRoute('sibling.api')?->gatherMiddleware())->not->toContain('api');
});

it('hands the route file to an invokable class', function () {
    siblingWith(['api' => PrefixedLoader::class])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('loader/sibling-api')
        ->and(siblingRoute('sibling.api')?->gatherMiddleware())->not->toContain('api');
});

it('hands the loader the feature too', function () {
    siblingWith(['api' => SlugLoader::class])->boot();

    expect(siblingRoute('sibling.api')?->uri())->toBe('sibling/sibling-api');
});

class PrefixedLoader
{
    public function __invoke(string $path): void
    {
        Route::prefix('loader')->group($path);
    }
}

class SlugLoader
{
    public function __invoke(string $path, Features $features): void
    {
        Route::prefix($features->slug())->group($path);
    }
}

class RouteGroupsExtension extends Extension
{
    /** @param  array<string, array<string, mixed>|Closure|class-string|null>  $groups */
    public function __construct(private array $groups) {}

    /** @return array<string, array<string, mixed>|Closure|class-string|null> */
    public function routeGroups(): array
    {
        return $this->groups;
    }
}

class GroupedSiblingServiceProvider extends SiblingServiceProvider
{
    /** @param  array<string, array<string, mixed>|Closure|class-string|null>  $groups */
    public function __construct(Application $app, private array $groups)
    {
        parent::__construct($app);
    }

    /**
     * Declared here rather than in the fixture, so the path derived from this
     * class's own file would point at the package root. Point it back.
     */
    protected function featuresPath(): string
    {
        return dirname(__DIR__).'/tests/__fixtures__/Sibling';
    }

    protected function name(): string
    {
        return 'Sibling';
    }

    /** @return array<string, array<string, mixed>|Closure|class-string|null> */
    protected function routeGroups(): array
    {
        return $this->groups;
    }
}
