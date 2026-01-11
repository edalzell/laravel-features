<?php

namespace Edalzell\Features;

use Edalzell\Features\Concerns\Features\HasConfiguration;
use Edalzell\Features\Concerns\Features\HasDatabase;
use Edalzell\Features\Concerns\Features\HasRoutes;
use Edalzell\Features\Concerns\Features\HasViews;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Feature
{
    use HasConfiguration, HasDatabase, HasRoutes, HasViews;

    private Filesystem $disk;

    public function __construct(public string $name)
    {
        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Features/'.$name),
        ]);
    }

    public function seeders(): Collection
    {
        return collect();
    }
}
