<?php

namespace Edalzell\Features\Composer;

use Composer\Package\RootPackageInterface;
use Composer\Script\Event;

class FeatureNamespaces
{
    public static function add(Event $event)
    {
        $package = $event->getComposer()->getPackage();

        (new self($package))->setAutoloads();
    }

    private function __construct(private RootPackageInterface $package) {}

    public function setAutoloads(): void
    {
        $autoload = $this->package->getAutoload();
        $autoloadDev = $this->package->getDevAutoload();

        $this
            ->autoloadFeatures($autoload, $autoloadDev)
            ->autoloadPackageFeatures($autoload, $autoloadDev);

        $this->package->setAutoload($autoload);
        $this->package->setDevAutoload($autoloadDev);

    }

    public function autoloadFeatures(array &$autoload, array &$autoloadDev): self
    {
        foreach ($this->featurePaths('features') as $path) {
            $featureName = basename($path);
            $rootNamespace = "Features\\{$featureName}\\";
            $dbRootNamespace = $rootNamespace.'Database\\';
            $rootPath = "features/{$featureName}/src/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "features/{$featureName}/database/factories";
            $seedersPath = "features/{$featureName}/database/seeders";

            $autoload['psr-4'][$dbRootNamespace.'Factories\\'] = $factoryPath;
            $autoload['psr-4'][$dbRootNamespace.'Seeders\\'] = $seedersPath;

            $autoloadDev['psr-4'][$rootNamespace.'Tests\\'] = "features/{$featureName}/tests";
        }

        return $this;
    }

    public function autoloadPackageFeatures(array &$autoload, array &$autoloadDev): self
    {
        if (empty($featurePaths = $this->featurePaths('vendor/*/*/features'))) {
            return $this;
        }

        $namespace = $this->packageNamespace($featurePaths);

        foreach ($featurePaths as $path) {
            // this will have a trailing slash
            $featureName = basename($path);
            $rootNamespace = "{$namespace}Features\\{$featureName}\\";
            $dbRootNamespace = $rootNamespace.'Database\\';
            $rootPath = "features/{$featureName}/src/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "features/{$featureName}/database/factories";
            $seedersPath = "features/{$featureName}/database/seeders";

            $autoload['psr-4'][$dbRootNamespace.'Factories\\'] = $factoryPath;
            $autoload['psr-4'][$dbRootNamespace.'Seeders\\'] = $seedersPath;

            $autoloadDev['psr-4'][$rootNamespace.'Tests\\'] = "features/{$featureName}/tests";
        }

        return $this;
    }

    private function featurePaths(string $path): array
    {
        if (empty($paths = glob(getcwd().DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'*'))) {
            return [];
        }

        return array_filter($paths, 'is_dir');
    }

    private function packageNamespace(array $featurePaths): string
    {
        // grab the first one, pop off the last segment, that's the package path
        $segments = explode(DIRECTORY_SEPARATOR, $featurePaths[0]);

        // remove the `features` segment
        array_pop($segments);
        $path = implode(DIRECTORY_SEPARATOR, $segments).DIRECTORY_SEPARATOR.'composer.json';

        $composer = json_decode(file_get_contents($path), true);

        return array_key_first($composer['autoload']['psr-4']);
    }
}
