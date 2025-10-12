<?php

namespace SilentZ\Features;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SilentZ\Features\Commands\FeaturesCommand;

class FeaturesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-features')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_features_table')
            ->hasCommand(FeaturesCommand::class);
    }
}
