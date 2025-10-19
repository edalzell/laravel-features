<?php

namespace SilentZ\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use SilentZ\Features\Concerns\Features\HasConfiguration;
use SilentZ\Features\Concerns\Features\HasDatabase;

class Feature
{
    use HasConfiguration, HasDatabase;

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
