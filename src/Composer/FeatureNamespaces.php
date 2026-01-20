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

        $featuresDir = getcwd().'/features';

        if (! is_dir($featuresDir)) {
            return;
        }

        // Find all feature directories
        $featurePaths = array_filter(glob($featuresDir.'/*'), 'is_dir');

        foreach ($featurePaths as $featurePath) {
            $featureName = basename($featurePath);
            $rootNamespace = "Features\\{$featureName}\\";
            $rootPath = "features/{$featureName}/src/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;

            $factoryPath = "features/{$featureName}/database/factories";
            $seedersPath = "features/{$featureName}/database/seeders";

            if (file_exists(getcwd()."/{$factoryPath}")) {
                $autoload['classmap'][] = $factoryPath;
            }

            if (file_exists(getcwd()."/{$seedersPath}")) {
                $autoload['classmap'][] = $seedersPath;
            }
        }

        $package->setAutoload($autoload);
    }
}
