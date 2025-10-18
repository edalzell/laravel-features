<?php

namespace SilentZ\Features\Composer;

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
        $features = array_filter(glob($featuresDir.'/*'), 'is_dir');

        foreach ($features as $featurePath) {
            $featureName = basename($featurePath);
            $namespace = "App\\Features\\{$featureName}\\";
            $path = "app/Features/{$featureName}/src/";

            $autoload['psr-4'][$namespace] = $path;
        }

        $package->setAutoload($autoload);
    }
}
