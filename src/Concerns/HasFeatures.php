<?php

namespace Edalzell\Features\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait HasFeatures
{
    public function registerFeatures(?string $path = null, ?string $namespacePrefix = null): void
    {
        $path ??= base_path('features');
        $namespacePrefix ??= Str::beforeLast(get_class($this), '\\').'\\Features';

        if (! File::exists($path)) {
            return;
        }

        collect(File::directories($path))
            ->filter(fn (string $dir) => File::exists($dir.'/src/ServiceProvider.php'))
            ->each(fn (string $dir) => $this->app->register($namespacePrefix.'\\'.basename($dir).'\\ServiceProvider'));
    }
}
