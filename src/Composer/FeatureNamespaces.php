<?php

namespace Edalzell\Features\Composer;

use Composer\Package\RootPackageInterface;
use Composer\Script\Event;

class FeatureNamespaces
{
    private array $autoload = [];

    private array $autoloadDev = [];

    public static function add(Event $event)
    {
        $package = $event->getComposer()->getPackage();

        (new self($package))->setAutoloads();
    }

    private function __construct(private RootPackageInterface $package)
    {
        $this->autoload = $this->package->getAutoload();
        $this->autoloadDev = $this->package->getDevAutoload();
    }

    public function setAutoloads(): void
    {
        $this
            ->autoloadFeatures()
            ->autoloadPackageFeatures();

        $this->package->setAutoload($this->autoload);
        $this->package->setDevAutoload($this->autoloadDev);
    }

    private function autoloadFeatures(): self
    {
        if (empty($featurePaths = $this->featurePaths('features'))) {
            return $this;
        }

        $this->generateNamespaces(
            $this->appFeaturesNamespace(),
            array_filter($featurePaths, 'is_dir')
        );

        return $this;
    }

    private function autoloadPackageFeatures(): self
    {
        if (empty($featurePaths = $this->featurePaths('vendor/*/*/features'))) {
            return $this;
        }

        $this->generateNamespaces(
            $this->appFeaturesNamespace($featurePaths),
            $featurePaths
        );

        return $this;
    }

    private function featurePaths(string $path): array
    {
        if (empty($paths = glob(getcwd().DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'*'))) {
            return [];
        }

        return array_filter($paths, 'is_dir');
    }

    private function generateNamespaces(string $namespace, array $featurePaths): void
    {
        foreach ($featurePaths as $path) {
            $featureName = basename($path);

            $rootNamespace = "{$namespace}\\{$featureName}\\";
            $dbRootNamespace = $rootNamespace.'Database\\';
            $rootPath = "features/{$featureName}/src/";

            $this->autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "features/{$featureName}/database/factories";
            $seedersPath = "features/{$featureName}/database/seeders";

            $this->autoload['psr-4'][$dbRootNamespace.'Factories\\'] = $factoryPath;
            $this->autoload['psr-4'][$dbRootNamespace.'Seeders\\'] = $seedersPath;

            $this->autoloadDev['psr-4'][$rootNamespace.'Tests\\'] = "features/{$featureName}/tests";
        }

    }

    private function appFeaturesNamespace(): string
    {
        return 'Features';
    }

    private function packageFeaturesNamespace(array $featurePaths): string
    {
        // grab the first one, pop off the last segment, that's the package path
        $segments = explode(DIRECTORY_SEPARATOR, $featurePaths[0]);

        // remove the `features` segment
        array_pop($segments);
        array_pop($segments);
        $path = implode(DIRECTORY_SEPARATOR, $segments).DIRECTORY_SEPARATOR.'composer.json';

        $composer = json_decode(file_get_contents($path), true);

        return array_key_first($composer['autoload']['psr-4']).'Features';
    }
}
