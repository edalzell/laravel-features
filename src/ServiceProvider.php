<?php

namespace SilentZ\Features;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot() {}

    public function register()
    {
        $this->features()
            ->filter(fn (Feature $feature) => $feature->provider !== null)
            ->each(fn (Feature $feature) => $this->app->register($feature->provider));
    }

    private function features(): Collection
    {
        $folders = Finder::create()
            ->in(app_path('Features'))
            ->directories();

        return collect($folders)
            ->map(fn (SplFileInfo $directory) => Feature::fromDirectory($directory));
    }
}
