<?php

namespace Edalzell\Features\Concerns;

use Illuminate\Support\Facades\File;
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

        collect(File::directories($path))
            ->filter(fn (string $dir) => File::exists($dir.'/src/ServiceProvider.php'))
            ->each(fn (string $dir) => $this->app->register($namespacePrefix.'\\'.basename($dir).'\\ServiceProvider'));
    }
}
