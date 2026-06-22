<?php

use Edalzell\Features\Concerns\HasFeatures;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

it('registers features at an explicit path and namespace', function () {
    File::expects('exists')->with('/features/path')->andReturns(true);
    File::expects('directories')->with('/features/path')->andReturns(['/features/path/MyFeature']);
    File::expects('exists')->with('/features/path/MyFeature/src/ServiceProvider.php')->andReturns(true);

    $app = mock(Application::class);
    $app->shouldReceive('register')
        ->once()
        ->with('My\\App\\MyFeature\\ServiceProvider');

    (new TestHasFeaturesProvider($app))->registerFeatures('/features/path', 'My\\App');
});

it('skips registration when path does not exist', function () {
    File::expects('exists')->with('/nonexistent')->andReturns(false);

    $app = mock(Application::class);
    $app->shouldNotReceive('register');

    (new TestHasFeaturesProvider($app))->registerFeatures('/nonexistent', 'My\\App');
});

class TestHasFeaturesProvider extends ServiceProvider
{
    use HasFeatures;

    public function boot(): void {}

    public function register(): void {}
}
