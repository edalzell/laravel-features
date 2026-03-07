<?php

namespace Edalzell\Features;

use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

abstract class PackageFeatureServiceProvider extends AggregateServiceProvider
{
    private ReflectionClass $reflection;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->reflection = new ReflectionClass(static::class);
    }

    public function register()
    {
        if (! File::exists($path = $this->featuresPath())) {
            return;
        }

        $disk = Storage::build(['driver' => 'local', 'root' => $path]);

        $this->providers = collect($disk->directories())
            ->filter(fn (string $name) => $disk->exists($name.'/src/ServiceProvider.php'))
            ->map(fn (string $name) => $this->featureProviderNamespace($name));

        parent::register();
    }

    private function featureProviderNamespace(string $name): string
    {
        return "{$this->reflection->getNamespaceName()}\\Features\\{$name}\\ServiceProvider";
    }

    private function featuresPath(): string
    {
        // path looks like '/some/folder/site/vendor/edalzell/my-features/src/ServiceProvider.php'
        // remove the last 2 segments, that's the package path
        $pathArray = explode(DIRECTORY_SEPARATOR, $this->reflection->getFileName());

        $packagePath = implode(
            DIRECTORY_SEPARATOR,
            array_slice($pathArray, 0, count($pathArray) - 2));

        return $packagePath.DIRECTORY_SEPARATOR.'features';
    }
}
