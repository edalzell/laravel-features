<?php

namespace Edalzell\Features\Concerns;

use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait HasFeatures
{
    protected function bootFeatures()
    {
        if (app()->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    protected function registerFeatures()
    {
        if (! File::exists(base_path('features'))) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => base_path('features')]);

        $this->providers = collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => app()->register('Features\\'.$name.'\\ServiceProvider'));
    }
}
