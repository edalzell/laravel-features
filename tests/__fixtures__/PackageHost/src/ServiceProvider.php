<?php

namespace Edalzell\Features\Tests\Fixtures\PackageHost;

use Edalzell\Features\Concerns\HasFeatures;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    use HasFeatures;

    public function boot(): void {}

    public function register(): void {}
}
