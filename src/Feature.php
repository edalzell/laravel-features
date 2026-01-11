<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Feature
{
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
