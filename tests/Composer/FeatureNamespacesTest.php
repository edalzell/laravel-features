<?php

use Brain\Monkey\Functions;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Script\Event;
use Edalzell\Features\Composer\FeatureNamespaces;

use function Brain\Monkey\Functions\when;

afterEach(function () {
    Brain\Monkey\tearDown();
});

it('adds feature classes to namespaces', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);
    $featuresDir = getcwd().'/features';

    when('is_dir')->justReturn(true);
    Functions\expect('glob')->once()->with($featuresDir.'/*')->andReturn([$featuresDir.'/One'])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([]);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe(['psr-4' => [
            'Features\\One\\' => 'features/One/src',
            'Features\\One\\Database\\Factories\\' => 'features/One/database/factories',
            'Features\\One\\Database\\Seeders\\' => 'features/One/database/seeders',
        ]])->getDevAutoload()->toBe(['psr-4' => [
            'Features\\One\\Tests\\' => 'features/One/tests',
        ]]);
});

it('adds package feature classes to namespaces', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    $composerJson = json_encode(['autoload' => ['psr-4' => ['Edalzell\\MyFeatures\\' => 'src/']]]);

    when('is_dir')->justReturn(true);
    when('file_get_contents')->justReturn($composerJson);
    Functions\expect('glob')->once()->with(getcwd().'/features/*')->andReturn([])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([getcwd().'/vendor/edalzell/my-features/features/   One']);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe(['psr-4' => [
            'Edalzell\\MyFeatures\\Features\\One\\' => 'vendor/edalzell/my-features/features/One/src',
            'Edalzell\\MyFeatures\\Features\\One\\Database\\Factories\\' => 'vendor/edalzell/my-features/features/One/database/factories',
            'Edalzell\\MyFeatures\\Features\\One\\Database\\Seeders\\' => 'vendor/edalzell/my-features/features/One/database/seeders',
        ]])->getDevAutoload()->toBe(['psr-4' => [
            'Edalzell\\MyFeatures\\Features\\One\\Tests\\' => 'vendor/edalzell/my-features/features/One/tests',
        ]]);
});
