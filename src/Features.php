<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Features
{
    public static Collection $features;

    public function __construct()
    {
        if (! isset(static::$features)) {
            static::$features = collect();
        }
    }

    public function register(Application $app): void
    {
        if (! file_exists($featuresDir = app_path('Features'))) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => $featuresDir]);

        if (empty($features = $disk->directories())) {
            return;
        }

        foreach ($features as $name) {
            if (! $disk->exists($name.'/src/ServiceProvider.php')) {
                continue;
            }

            static::$features->push(new Feature($name));

            $app->register('App\\Features\\'.$name.'\\ServiceProvider');
        }
    }

    public function seeders(): array
    {
        return static::$features
            ->flatMap
            ->seeders()
            ->all();
    }
}
