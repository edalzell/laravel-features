<?php

use Edalzell\Features\Features;
use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Symfony\Component\Filesystem\Path;

uses(TestCase::class)->in(__DIR__);

function mockOnDemandDisk(string $path = '', bool $package = false): Filesystem
{
    $localDisk = Storage::fake('local');
    $root = $package ? Path::normalize(__DIR__.'__fixtures__/TwoWords') : base_path($path);

    Storage::shouldReceive('build')->with([
        'driver' => 'local',
        'root' => $root,
    ])->andReturn($localDisk);

    return $localDisk;
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
        ->path(base_path($featurePath))
        ->name($name)
        ->namespace('Features\\'.$name);

    return [$features, $provider];
}

function tidy(string $path): string
{
    return str_replace('/', '\\', $path);
}

class TestServiceProvider extends FeatureServiceProvider
{
    protected function name(): string
    {
        return 'TwoWords';
    }
}
