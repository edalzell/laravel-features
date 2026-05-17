<?php

namespace Edalzell\Features\Providers;

abstract class PackageServiceProvider extends FeatureServiceProvider
{
    protected function featuresPath(): string
    {
        return packageRoot($this->reflection->getFileName());
    }
}
