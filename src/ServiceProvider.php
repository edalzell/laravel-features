<?php

namespace Edalzell\Features;

use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Make::class);
        }
    }

    public function register()
    {
        if (! File::exists(base_path('features'))) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => base_path('features')]);

        collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => $this->app->register('Features\\'.$name.'\\ServiceProvider'));
    }
}
