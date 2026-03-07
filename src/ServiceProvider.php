<?php

namespace Edalzell\Features;

use Edalzell\Features\Console\Commands\Make;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ServiceProvider extends AggregateServiceProvider
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

        $this->providers = collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->map(fn (string $name) => 'Features\\'.$name.'\\ServiceProvider');

        parent::register();
    }
}
