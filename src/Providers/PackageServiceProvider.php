<?php

namespace Edalzell\Features\Providers;

abstract class PackageServiceProvider extends FeatureServiceProvider
{
    protected function featuresPath(): string
    {
        return dirname($this->reflection->getFileName(), 2);
    }
}
