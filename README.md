[![Latest Version on Packagist](https://img.shields.io/packagist/v/edalzell/laravel-features.svg?style=flat-square)](https://packagist.org/packages/edalzell/laravel-features)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/edalzell/laravel-features/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/edalzell/laravel-features/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/edalzell/laravel-features/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/edalzell/laravel-features/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/edalzell/laravel-features.svg?style=flat-square)](https://packagist.org/packages/edalzell/laravel-features)

Add self-contained features to your Laravel app or package, including all resources/routes/etc.

```bash
.
└── app/
...
└── features/
  │   └── MyGreatFeature/
  │       ├── config/
  │       │   └── my-great-feature.php
  │       ├── database/
  │       │   ├── factories
  │       │   ├── migrations
  │       │   └── seeders
  │       ├── resources
  │       ├── routes
  │       └── src/
  │           ├── Models
  │           ├── ...
  │           └── ServiceProvider.php
```

Each feature behaves like a mini Laravel app. The following are auto-registered and booted:

| Phase | What |
|---|---|
| Register | Config, Migrations, Routes, Seeders, Views |
| Boot | Config publishing, Listeners, Policies, Seeders |

## Installation

You can install the package via composer:

```bash
composer require edalzell/laravel-features
```

## Usage

To add a new feature in your app:

```bash
php artisan make:feature MyGreatFeature
```

To add feature to a package:

```bash
php artisan make:feature MyGreatFeature the-dev/my-package
```

This creates a `ServiceProvider` that extends `FeatureServiceProvider` — everything is auto-registered with no further code required.

### Option 1: Extend `FeatureServiceProvider`

The zero-friction path. Your provider gets `boot()` and `register()` for free:

```php
class MyGreatFeatureServiceProvider extends FeatureServiceProvider
{
    // nothing needed — everything is auto-registered
}
```

Override any of these protected methods to customise behaviour:

```php
protected function configFileName(): string      // default: kebab-cased feature name
protected function configGroup(): string         // default: '' (no subdirectory)
protected function configPublishHandle(): string // default: kebab-cased feature name
protected function featuresPath(): string        // default: base_path('features/FeatureName')
```

### Option 2: Standalone `Features` object

When your provider already extends another class, wire up `Features` directly:

```php
use Edalzell\Features\Features;

class MyServiceProvider extends SomeOtherProvider
{
    private Features $features;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->features = (new Features($this))
            ->path($this->featuresPath())
            ->name($this->name())
            ->configFileName($this->configFileName())
            ->configGroup($this->configGroup())
            ->configPublishHandle($this->configPublishHandle());
    }

    public function boot(): void
    {
        $this->features->bootFeature();
    }

    public function register(): void
    {
        $this->features->registerFeature();
    }
}
```

`Features` derives the path, namespace, and app from your provider via reflection. You only need to call the fluent setters when overriding the defaults.

### Auto-discovering features in a package

Use the `HasFeatures` trait in your package's main service provider to automatically register all features from a directory:

```php
use Edalzell\Features\Concerns\HasFeatures;

class MyPackageServiceProvider extends ServiceProvider
{
    use HasFeatures;

    public function register(): void
    {
        $this->registerFeatures();
    }
}
```

By default it looks for features in `<package-root>/features/` and expects providers at `YourPackage\Features\FeatureName\ServiceProvider`. Pass explicit arguments to override:

```php
$this->registerFeatures('/path/to/features', 'My\\App\\Features');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
