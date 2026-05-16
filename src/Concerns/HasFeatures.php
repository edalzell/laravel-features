<?php

namespace Edalzell\Features\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

trait HasFeatures
{
    public function registerFeatures()
    {
        $reflection = new ReflectionClass($this);
        $packagePath = packageRoot($reflection->getFileName());

        if (! File::exists($packagePath.'/features')) {
            return;
        }

        $namespace = $reflection->getNamespaceName();

        $disk = Storage::build(['driver' => 'local', 'root' => $packagePath.'/features']);

        collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => $this->app->register($namespace.'\\Features\\'.$name.'\\ServiceProvider'));
    }
}
