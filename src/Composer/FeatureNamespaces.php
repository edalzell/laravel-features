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

        $this->generateNamespaces($this->featuresNamespace(), $featurePaths);

        return $this;
    }

    private function autoloadPackageFeatures(): self
    {
        if (empty($featurePaths = $this->featurePaths('vendor/*/*/features'))) {
            return $this;
        }

        $this->generateNamespaces(
            $this->featuresNamespace($featurePaths),
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
            $featurePath = ltrim(str_replace(getcwd(), '', $path), DIRECTORY_SEPARATOR);

            $rootNamespace = "{$namespace}\\{$featureName}\\";
            $dbRootNamespace = $rootNamespace.'Database\\';
            $rootPath = "{$featurePath}/src";

            $this->autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "{$featurePath}/database/factories";
            $seedersPath = "{$featurePath}/database/seeders";

            $this->autoload['psr-4'][$dbRootNamespace.'Factories\\'] = $factoryPath;
            $this->autoload['psr-4'][$dbRootNamespace.'Seeders\\'] = $seedersPath;

            $this->autoloadDev['psr-4'][$rootNamespace.'Tests\\'] = "{$featurePath}/tests";
        }

    }

    private function featuresNamespace(array $featurePaths = []): string
    {
        if (empty($featurePaths)) {
            return 'Features';
        }

        $composerJson = file_get_contents($this->getComposerPath($featurePaths[0]));
        $composer = json_decode($composerJson, true);

        return array_key_first($composer['autoload']['psr-4']).'Features';
    }

    private function getComposerPath(string $featurePath): string
    {
        return packageRoot($featurePath).DIRECTORY_SEPARATOR.'composer.json';
    }
}
