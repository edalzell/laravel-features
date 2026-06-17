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

it('uses the app feature service provider stub', function () {
    $this->artisan('make:feature', ['name' => 'MyFeature'])->assertSuccessful();

    $contents = file_get_contents($this->tempDir.'/features/MyFeature/src/ServiceProvider.php');

    expect($contents)->toContain('use Edalzell\Features\Providers\FeatureServiceProvider;');
});

it('generates a provider for a multi-word feature name', function () {
    $this->artisan('make:feature', ['name' => 'TwoWords'])->assertSuccessful();

    expect(file_exists($this->tempDir.'/features/TwoWords/src/ServiceProvider.php'))->toBeTrue();

    $contents = file_get_contents($this->tempDir.'/features/TwoWords/src/ServiceProvider.php');

    expect($contents)->toContain('namespace Features\TwoWords;');
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

    it('uses the package service provider stub', function () {
        $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

        $contents = file_get_contents($this->packageDir.'/features/MyFeature/src/ServiceProvider.php');

        expect($contents)->toContain('use Edalzell\Features\Providers\PackageServiceProvider;');
    });

    describe('adding HasFeatures to package service provider', function () {
        beforeEach(function () {
            file_put_contents($this->packageDir.'/composer.json', json_encode([
                'autoload' => ['psr-4' => ['TheDev\\ThePackage\\' => 'src/']],
                'extra' => ['laravel' => ['providers' => ['TheDev\\ThePackage\\ServiceProvider']]],
            ]));
        });

        it('adds HasFeatures when the service provider has no register method', function () {
            file_put_contents($this->packageDir.'/src/ServiceProvider.php', <<<'PHP'
<?php

namespace TheDev\ThePackage;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void {}
}
PHP);

            $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

            $contents = file_get_contents($this->packageDir.'/src/ServiceProvider.php');

            expect($contents)
                ->toContain('use Edalzell\Features\Concerns\HasFeatures;')
                ->toContain('use HasFeatures;')
                ->toContain('public function register(): void')
                ->toContain('$this->registerFeatures();');
        });

        it('inserts registerFeatures into an existing register method', function () {
            file_put_contents($this->packageDir.'/src/ServiceProvider.php', <<<'PHP'
<?php

namespace TheDev\ThePackage;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        parent::register();
    }
}
PHP);

            $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

            $contents = file_get_contents($this->packageDir.'/src/ServiceProvider.php');

            expect($contents)
                ->toContain('use Edalzell\Features\Concerns\HasFeatures;')
                ->toContain('use HasFeatures;')
                ->toContain('$this->registerFeatures();')
                ->toContain('parent::register();');
        });

        it('does not add HasFeatures if already present', function () {
            file_put_contents($this->packageDir.'/src/ServiceProvider.php', <<<'PHP'
<?php

namespace TheDev\ThePackage;

use Edalzell\Features\Concerns\HasFeatures;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    use HasFeatures;

    public function register(): void
    {
        $this->registerFeatures();
    }
}
PHP);

            $this->artisan('make:feature', ['name' => 'MyFeature', 'package' => 'the-dev/the-package'])->assertSuccessful();

            $contents = file_get_contents($this->packageDir.'/src/ServiceProvider.php');

            expect(substr_count($contents, 'HasFeatures'))->toBe(2);
        });
    });
});
