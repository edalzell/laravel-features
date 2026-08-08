<?php

use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
use Illuminate\Support\Facades\Route;

function siblingRoute(string $name): ?Illuminate\Routing\Route
{
    return collect(Route::getRoutes()->getRoutes())
        ->first(fn (Illuminate\Routing\Route $route) => $route->getName() === $name);
}

it('puts routes/web.php in the web middleware group', function () {
    (new SiblingServiceProvider(app()))->register();

    expect(siblingRoute('sibling.web')?->gatherMiddleware())->toContain('web');
});

it('puts routes/api.php in the api group and prefixes it', function () {
    (new SiblingServiceProvider(app()))->register();

    $route = siblingRoute('sibling.api');

    expect($route?->uri())->toBe('api/sibling-api')
        ->and($route?->gatherMiddleware())->toContain('api');
});

it('lets the app change a group through config', function () {
    config()->set('features.route_groups.api', [
        'middleware' => 'api',
        'prefix' => 'api/v1',
        'as' => 'api.v1.',
    ]);

    (new SiblingServiceProvider(app()))->register();

    expect(siblingRoute('api.v1.sibling.api')?->uri())->toBe('api/v1/sibling-api');
});

it('lets a feature override its own groups', function () {
    (new OverridingSiblingServiceProvider(app()))->register();

    expect(siblingRoute('sibling.api')?->uri())->toBe('internal/sibling-api');
});

it('adds no middleware group or prefix when there is no entry for the file', function () {
    config()->set('features.route_groups', []);

    (new SiblingServiceProvider(app()))->register();

    expect(siblingRoute('sibling.api')?->uri())->toBe('sibling-api')
        ->and(siblingRoute('sibling.api')?->gatherMiddleware())->not->toContain('api');
});

class OverridingSiblingServiceProvider extends SiblingServiceProvider
{
    /**
     * Declared here rather than in the fixture, so the path derived from this
     * class's own file would point at the package root. Point it back.
     */
    protected function featuresPath(): string
    {
        return dirname(__DIR__).'/tests/__fixtures__/Sibling';
    }

    /** @return array<string, array<string, mixed>> */
    protected function routeGroups(): array
    {
        return ['api' => ['prefix' => 'internal']];
    }
}
