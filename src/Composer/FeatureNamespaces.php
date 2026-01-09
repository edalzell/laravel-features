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

        $autoload['exclude-from-classmap'] = ['app/Features/*/database/factories', 'app/Features/*/database/seeders'];

        // Find all feature directories
        $featurePaths = array_filter(glob($featuresDir.'/*'), 'is_dir');

        foreach ($featurePaths as $featurePath) {
            $featureName = basename($featurePath);
            $rootNamespace = "App\\Features\\{$featureName}\\";
            $rootPath = "app/Features/{$featureName}/src/";

            $autoload['psr-4'][$rootNamespace] = $rootPath;
        }

        $package->setAutoload($autoload);
    }
}
