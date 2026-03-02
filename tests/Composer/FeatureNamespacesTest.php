<?php

use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Script\Event;
use Edalzell\Features\Composer\FeatureNamespaces;

use function Brain\Monkey\Functions\when;

it('adds feature classes to namespaces', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    when('glob')->justReturn(['foo' => 'bar']);
    // set up package
    // only needs `composer.json`

    // set up package feature

    FeatureNamespaces::add(new Event('foo', $composer, new NullIO));

    dd($composer->getPackage()->getAutoload());
});
