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

        $featuresDir = getcwd().'/features';

        if (! is_dir($featuresDir)) {
            return;
        }

        // Find all feature directories
        $featurePaths = array_filter(glob($featuresDir.'/*'), 'is_dir');

        foreach ($featurePaths as $featurePath) {
            $featureName = basename($featurePath);
            $rootNamespace = "Features\\{$featureName}\\";
            $dbRootNamespace = $rootNamespace.'Database\\';
            $rootPath = "features/{$featureName}/src/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "features/{$featureName}/database/factories";
            $seedersPath = "features/{$featureName}/database/seeds";

            $autoload['psr-4'][$dbRootNamespace.'Factories\\'] = $factoryPath;
            $autoload['psr-4'][$dbRootNamespace.'Seeders\\'] = $seedersPath;

            $autoloadDev['psr-4'][$rootNamespace.'Tests\\'] = "features/{$featureName}/tests/";
        }

        $package->setAutoload($autoload);
        $package->setDevAutoload($autoloadDev);

    }
}
