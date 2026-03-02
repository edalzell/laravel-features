<?php

use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Script\Event;
use Edalzell\Features\Composer\FeatureNamespaces;

use function Patchwork\redefine;
use function Patchwork\restore;

it('adds feature classes to namespaces', function () {
    //
    $glob = redefine('glob', fn () => ['foo' => 'bar']);
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    // set up package
    // only needs `composer.json`

    // set up package feature

    FeatureNamespaces::add(new Event('foo', $composer, new NullIO));

    //restore($glob);
    dd($composer->getPackage()->getAutoload());
});
