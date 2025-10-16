<?php

namespace SilentZ\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use SilentZ\Features\Concerns\Features\HasConfiguration;
use SilentZ\Features\Concerns\Features\HasDatabase;

class Feature
{
    use HasConfiguration, HasDatabase;

    private Filesystem $disk;

    public readonly string $slug;

    public function __construct(public string $name)
    {
        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Features/'.$name),
        ]);

        $this->slug = str($name)->slug()->toString();
    }

    public function registerProvider(Application $app): void
    {
        if ($this->exists('ServiceProvider.php')) {
            $app->register('App\\Features\\'.$this->name.'\\ServiceProvider');
        }
    }

    private function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }
}
