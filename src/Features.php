<?php

namespace Edalzell\Features;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Features
{
    public function register(Application $app): void
    {
        if (! File::exists(app_path('Features'))) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => app_path('Features')]);

        collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => $app->register('App\\Features\\'.$name.'\\ServiceProvider'));
    }
}
