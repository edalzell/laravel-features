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

        $generator = new NamespaceGenerator;

        $generator->autoloadFeatures($autoload, $autoloadDev);
        $generator->autoloadPackageFeatures($autoload, $autoloadDev);

        $package->setAutoload($autoload);
        $package->setDevAutoload($autoloadDev);
    }
}
