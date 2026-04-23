<?php

namespace Edalzell\Features\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

trait HasPackageFeatures
{
    public function registerFeatures()
    {
        if (! File::exists($path = $this->featuresPath())) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => $path]);

        collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->each(fn (string $name) => app()->register($this->featureProviderNamespace($name)));
    }

    private function featureProviderNamespace(string $name): string
    {
        return "{$this->reflection()->getNamespaceName()}\\Features\\{$name}\\ServiceProvider";
    }

    private function featuresPath(): string
    {
        // path looks like '/some/folder/site/vendor/edalzell/my-features/src/ServiceProvider.php'
        // remove the last 2 segments, that's the package path
        $pathArray = explode(DIRECTORY_SEPARATOR, $this->reflection()->getFileName());

        $packagePath = implode(
            DIRECTORY_SEPARATOR,
            array_slice($pathArray, 0, count($pathArray) - 2));

        return $packagePath.DIRECTORY_SEPARATOR.'features';
    }

    private function reflection(): ReflectionClass
    {
        return new ReflectionClass(static::class);
    }
}
