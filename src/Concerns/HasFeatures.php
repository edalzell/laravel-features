<?php

namespace Edalzell\Features\Concerns;

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;
use Illuminate\Support\Facades\File;
use ReflectionClass;

trait HasFeatures
{
    public function registerFeatures(
        ?string $path = null,
        ?string $namespacePrefix = null,
        bool $publishesConfig = true,
    ): void {
        $reflection = new ReflectionClass($this);
        $packageRoot = dirname($reflection->getFileName(), 2);
        $usingPackageFeatures = $path === null;
        $path ??= $packageRoot.'/features';
        $namespacePrefix ??= $reflection->getNamespaceName().'\\Features';

        if (! File::exists($path)) {
            return;
        }

        $configGroup = $usingPackageFeatures ? $this->packageConfigGroup($packageRoot) : '';

        // A host package can register before this package's provider has; the
        // registry has to be the shared one either way or the feature providers
        // won't find their own entries.
        $this->app->singletonIf(FeatureRegistry::class);
        $registry = $this->app->make(FeatureRegistry::class);

        collect(File::directories($path))
            ->filter(fn (string $dir) => File::exists($dir.'/src/ServiceProvider.php'))
            ->each(fn (string $dir) => $registry->register(new Feature($dir, $namespacePrefix, $configGroup, $publishesConfig)));
    }

    private function packageConfigGroup(string $packageRoot): string
    {
        $composerPath = $packageRoot.'/composer.json';

        if (! File::exists($composerPath)) {
            return '';
        }

        $name = json_decode(File::get($composerPath), true)['name'] ?? null;

        if (! is_string($name) || ! str_contains($name, '/')) {
            return '';
        }

        return basename($name);
    }
}
