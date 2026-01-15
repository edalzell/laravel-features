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
            $autoload['classmap'][] = "features/{$featureName}/database/factories";
            $autoload['classmap'][] = "features/{$featureName}/database/seeds";

        }

        $package->setAutoload($autoload);
    }
}
