<?php

namespace Edalzell\Features;

use Edalzell\Features\Concerns\Features\HasConfiguration;
use Edalzell\Features\Concerns\Features\HasDatabase;
use Edalzell\Features\Concerns\Features\HasRoutes;
use Edalzell\Features\Concerns\Features\HasViews;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class Feature
{
    use HasConfiguration, HasDatabase, HasRoutes, HasViews;

    private Filesystem $disk;

    public readonly string $slug;

    public function __construct(public string $name, private FeatureServiceProvider $provider)
    {
        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Features/'.$name),
        ]);

        $this->slug = str($name)->kebab()->toString();
    }

    public function boot(): void
    {
        $this->bootConfig();
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function path(string $path): string
    {
        return $this->disk->path($path);
    }

    public function register(): void
    {
        $this
            ->registerConfig($this)
            ->registerDatabase();
    }
}
