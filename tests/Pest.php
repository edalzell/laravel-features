<?php

use Edalzell\Features\Features;
use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

uses(TestCase::class)->in(__DIR__);

/**
 * An empty directory to build a feature in. `mockFeatures()` points its feature
 * here, so what a test puts on this disk is what the feature has.
 */
function mockOnDemandDisk(string $path = ''): Filesystem
{
    return Storage::fake('local');
}

function fakeFeatureRoot(): string
{
    return storage_path('framework/testing/disks/local');
}

/** The path `Features` will build for a file on the on-demand disk: always `/`, whatever the platform. */
function featureFile(string $relative): string
{
    return fakeFeatureRoot().'/'.$relative;
}

/**
 * @template T of \Illuminate\Support\ServiceProvider
 *
 * @param  class-string<T>  $class
 * @return T&MockInterface
 */
function mockServiceProvider(string $class, mixed $app = null)
{
    return mock($class, [$app ?? app()])
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
}

/**
 * Creates a Features instance bound to a mock provider, with path, name, and
 * namespace set from the given feature directory (relative to base_path).
 *
 * @return array{0: Features, 1: mixed}
 */
function mockFeatures(string $featurePath = 'features/TwoWords', ?string $providerClass = null, mixed $app = null): array
{
    $provider = mockServiceProvider($providerClass ?? TestServiceProvider::class, $app);
    $name = basename($featurePath);
    $features = (new Features($provider))
        ->path(fakeFeatureRoot())
        ->name($name)
        ->namespace('Features\\'.$name);

    return [$features, $provider];
}

function tidy(string $path): string
{
    return str_replace('/', '\\', $path);
}

function fixturePath(string $path = ''): string
{
    return rtrim(__DIR__.'/__fixtures__/'.$path, '/');
}

class TestServiceProvider extends FeatureServiceProvider
{
    protected function featuresPath(): string
    {
        return fakeFeatureRoot();
    }

    protected function name(): string
    {
        return 'TwoWords';
    }
}
