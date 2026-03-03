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

    public static function autoloadFeatures(array &$autoload, array &$autoloadDev): void
    {
        $featurePaths = array_filter(glob(getcwd().'/features/*'), 'is_dir');

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

    public static function autoloadPackageFeatures(array &$autoload, array &$autoloadDev): void
    {
        $packageFeaturePaths = array_filter(glob(getcwd().'vendor/*/*/features/*'), 'is_dir');

        foreach ($packageFeaturePaths as $packageFeaturePath) {
            //
        }

    }
}
