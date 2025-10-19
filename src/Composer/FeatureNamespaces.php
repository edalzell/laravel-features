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

        $featuresDir = getcwd().'/app/Features';

        if (! is_dir($featuresDir)) {
            return;
        }

        // Find all feature directories
        $featurePaths = array_filter(glob($featuresDir.'/*'), 'is_dir');

        foreach ($featurePaths as $featurePath) {
            $featureName = basename($featurePath);
            $rootNamespace = "App\\Features\\{$featureName}\\";
            $rootPath = "app/Features/{$featureName}/src/";
            $dbNamespace = "Features\\{$featureName}\\Database\\";
            $dbPath = "app/Features/{$featureName}/src/database/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;
            $autoload['psr-4'][$dbNamespace.'Seeders\\'] = $dbPath.'seeders/';
            $autoload['psr-4'][$dbNamespace.'Factories\\'] = $dbPath.'factories/';
        }

        $package->setAutoload($autoload);
    }
}
