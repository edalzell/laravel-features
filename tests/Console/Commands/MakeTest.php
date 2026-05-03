<?php

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    file_put_contents($this->tempDir.'/composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/']],
    ]));

    $this->app->useAppPath($this->tempDir.'/app');
    $this->app->setBasePath($this->tempDir);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('generates a provider', function () {
    $this->artisan('make:feature', ['name' => 'MyFeature'])->assertSuccessful();

    expect(file_exists($this->tempDir.'/features/MyFeature/src/ServiceProvider.php'))->toBeTrue();
});

describe('package features', function () {
    beforeEach(function () {
        $this->packageDir = $this->tempDir.'/vendor/the-dev/the-package';
        mkdir($this->packageDir.'/src', 0755, true);
        file_put_contents($this->packageDir.'/composer.json', json_encode([
            'autoload' => ['psr-4' => ['TheDev\\ThePackage\\' => 'src/']],
        ]));
    });

    it('generates a provider in the package features folder', function () {
        $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

        expect(file_exists($this->packageDir.'/features/MyFeature/src/ServiceProvider.php'))->toBeTrue();
    });

    it('uses the package namespace', function () {
        $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

        $contents = file_get_contents($this->packageDir.'/features/MyFeature/src/ServiceProvider.php');

        expect($contents)->toContain('namespace TheDev\\ThePackage\\Features\\MyFeature;');
    });
});

it('adds pre-autoload-dump composer hook', function () {
    $this->artisan('make:feature', ['name' => 'MyFeature'])->assertSuccessful();

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    expect($composerJson)->toMatchArray([
        'scripts' => [
            'pre-autoload-dump' => [
                'Edalzell\Features\Composer\FeatureNamespaces::add',
            ],
        ],
    ]);
});
