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

it('does nothing when no features are found', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    Functions\expect('glob')
        ->once()->with(getcwd().'/features/*')->andReturn([])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([]);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe([])
        ->getDevAutoload()->toBe([]);
});

it('throws when the package composer.json cannot be read', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    when('is_dir')->justReturn(true);
    when('file_get_contents')->justReturn(false);
    Functions\expect('glob')
        ->once()->with(getcwd().'/features/*')->andReturn([])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')
        ->andReturn([getcwd().'/vendor/edalzell/my-features/features/One']);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));
})->throws(Exception::class, 'Cannot read composer.json');

it('throws when the package composer.json is missing psr-4 autoload', function () {
    $package = new RootPackage('edalzell/my-features', '1.0', 'v1.1');
    $composer = tap(new Composer)->setPackage($package);

    when('is_dir')->justReturn(true);
    when('file_get_contents')->justReturn(json_encode(['name' => 'edalzell/my-features']));
    Functions\expect('glob')
        ->once()->with(getcwd().'/features/*')->andReturn([])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')
        ->andReturn([getcwd().'/vendor/edalzell/my-features/features/One']);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));
})->throws(Exception::class, 'missing autoload.psr-4');

it('uses package namespace for local features when root is not an app', function () {
    $package = new RootPackage('myvendor/my-package', '1.0', 'v1.0');
    $package->setAutoload(['psr-4' => ['MyVendor\\MyPackage\\' => 'src/']]);
    $composer = tap(new Composer)->setPackage($package);
    $featuresDir = getcwd().'/features';

    when('is_dir')->justReturn(true);
    Functions\expect('glob')->once()->with($featuresDir.'/*')->andReturn([$featuresDir.'/One'])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([]);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe(['psr-4' => [
            'MyVendor\\MyPackage\\' => 'src/',
            'MyVendor\\MyPackage\\Features\\One\\' => 'features/One/src',
            'MyVendor\\MyPackage\\Features\\One\\Database\\Factories\\' => 'features/One/database/factories',
            'MyVendor\\MyPackage\\Features\\One\\Database\\Seeders\\' => 'features/One/database/seeders',
        ]])->getDevAutoload()->toBe(['psr-4' => [
            'MyVendor\\MyPackage\\Features\\One\\Tests\\' => 'features/One/tests',
        ]]);
});

it('uses plain Features namespace for local features when root is a Laravel app', function () {
    $package = new RootPackage('myapp/app', '1.0', 'v1.0');
    $package->setAutoload(['psr-4' => ['App\\' => 'app/']]);
    $composer = tap(new Composer)->setPackage($package);
    $featuresDir = getcwd().'/features';

    when('is_dir')->justReturn(true);
    Functions\expect('glob')->once()->with($featuresDir.'/*')->andReturn([$featuresDir.'/One'])
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([]);

    FeatureNamespaces::add(new Event('pre-autoload-dump', $composer, new NullIO));

    expect($package)
        ->getAutoload()->toBe(['psr-4' => [
            'App\\' => 'app/',
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
        ->andAlsoExpectIt()->once()->with(getcwd().'/vendor/*/*/features/*')->andReturn([getcwd().'/vendor/edalzell/my-features/features/One']);

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
