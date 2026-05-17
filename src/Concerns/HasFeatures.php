<?php

namespace Edalzell\Features\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

trait HasFeatures
{
    public function registerFeatures(?string $path = null, ?string $namespacePrefix = null): void
    {
        $reflection = new ReflectionClass($this);
        $path ??= packageRoot($reflection->getFileName()).'/features';
        $namespacePrefix ??= $reflection->getNamespaceName().'\\Features';

        if (! File::exists($path)) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => $path]);

        collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => $this->app->register($namespacePrefix.'\\'.$name.'\\ServiceProvider'));
    }
}
