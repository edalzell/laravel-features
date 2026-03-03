<?php

namespace Edalzell\Features\Composer;

use Composer\Script\Event;

class FeatureNamespaces
{
    public static function add(Event $event)
    {
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $autoload = $package->getAutoload();
        $autoloadDev = $package->getDevAutoload();

        static::autoloadFeatures($autoload, $autoloadDev);
        static::autoloadPackageFeatures($autoload, $autoloadDev);

        $package->setAutoload($autoload);
        $package->setDevAutoload($autoloadDev);
    }

    private static function autoloadFeatures(array &$autoload, array &$autoloadDev): void
    {
        $featurePaths = static::featurePaths('features');

        foreach ($featurePaths as $featurePath) {
            $featureName = basename($featurePath);
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
    }

    private static function autoloadPackageFeatures(array &$autoload, array &$autoloadDev): void
    {
        $packageFeaturePaths = static::featurePaths('vendor/*/*/features');

        foreach ($packageFeaturePaths as $packageFeaturePath) {
            //
        }
    }

    private static function featurePaths(string $path): array
    {
        if (empty($paths = glob(getcwd().DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'*'))) {
            return [];
        }

        return array_filter($paths, 'is_dir');
    }
}
