<?php

use Brain\Monkey\Functions;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Script\Event;
use Edalzell\Features\Composer\FeatureNamespaces;

use function Brain\Monkey\Functions\when;

it('adds feature classes to namespaces', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    when('is_dir')->justReturn(true);
    $featuresDir = getcwd().'/features';
    Functions\expect('glob')->once()->with($featuresDir.'/*')->andReturn([$featuresDir.'/One'])
        ->andAlsoExpectIt()->once()->with(getcwd().'vendor/*/*/features/*')->andReturn([]);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe(['psr-4' => [
            'Features\\One\\' => 'features/One/src/',
            'Features\\One\\Database\\Factories\\' => 'features/One/database/factories',
            'Features\\One\\Database\\Seeders\\' => 'features/One/database/seeders',
        ]])->getDevAutoload()->toBe(['psr-4' => [
            'Features\\One\\Tests\\' => 'features/One/tests',
        ]]);
});
