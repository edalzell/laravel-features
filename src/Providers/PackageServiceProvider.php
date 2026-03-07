<?php

namespace Edalzell\Features\Providers;

abstract class PackageServiceProvider extends FeatureServiceProvider
{
    protected function featuresPath(): string
    {
        // path looks like '/some/folder/site/vendor/edalzell/my-features/src/ServiceProvider.php'
        // remove the last 2 segments, that's the package path
        $pathArray = explode(DIRECTORY_SEPARATOR, $this->reflection->getFileName());

        return implode(
            DIRECTORY_SEPARATOR,
            array_slice($pathArray, 0, count($pathArray) - 2));
    }
}
