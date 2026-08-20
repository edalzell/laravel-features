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
| Boot | Config publishing, Listeners, Livewire components, Policies, Seeders |

## Route groups

A feature's route files are put in a route group, chosen by filename, the same way the framework does it for an application's own route files:

| File | Group |
|---|---|
| `routes/web.php` | `web` middleware |
| `routes/api.php` | `api` middleware, `api` prefix |
| anything else | no middleware group, no prefix |

Without this, `loadRoutesFrom()` is a bare `require` — a feature's `routes/web.php` would get no session or CSRF, and `routes/api.php` no throttling and no prefix.

Publish the config to change a group for all features at once — to add an API version, for example:

```bash
php artisan vendor:publish --tag=features-config
```

```php
// config/features.php
'route_groups' => [
    'web' => ['middleware' => 'web'],
    'api' => ['middleware' => 'api', 'prefix' => 'api/v1', 'as' => 'api.v1.'],
],
```

Or override `routeGroups()` on one feature's service provider:

```php
protected function routeGroups(): array
{
    return ['api' => ['middleware' => ['api', 'auth:sanctum'], 'prefix' => 'api/internal']];
}
```

Set an entry to `null`, or remove it, and that file gets no middleware group and no prefix — only what it declares itself.

## Livewire components

Livewire only looks for components in the app's own locations, so it never sees a feature's. Put class components in `src/Livewire`, and single- or multi-file components in `resources/views/livewire`. The feature's directories are registered as a Livewire namespace, so Livewire resolves and names them itself:

```
MyGreatFeature/
├── resources/
│   └── views/
│       └── livewire/
│           ├── greeting.blade.php    -> <livewire:my-great-feature::greeting />
│           └── checklist/            -> <livewire:my-great-feature::checklist />
│               ├── checklist.php
│               └── checklist.blade.php
└── src/
    └── Livewire/
        ├── PostList.php              -> <livewire:my-great-feature::post-list />
        └── Posts/
            ├── Index.php             -> <livewire:my-great-feature::posts />
            └── ShowPost.php          -> <livewire:my-great-feature::posts.show-post />
```

Single- and multi-file components follow Livewire's own rules — a single-file component is a `.blade.php` holding a `new class extends Component` block, a multi-file component a directory holding `name.php` and `name.blade.php` alongside any `name.js` or `name.css`. Nothing is scanned: Livewire resolves a name the first time it is used, exactly as it does for the app's own components.

The feature's slug goes in front so two features can each have a `PostList`. Change it, or drop it, on one feature's service provider:

```php
protected function livewireNamespace(): string
{
    return '';
}
```

An empty string registers the feature's directories as plain locations instead, so its components answer to their bare names.

This needs Livewire 4, which introduced both the namespace registration and view-based components. Livewire is not a dependency of this package: when it isn't installed, nothing is registered and nothing breaks.

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
protected function featuresPath(): string        // default: derived from the provider's own location
protected function livewireNamespace(): string   // default: kebab-cased feature name
protected function routeGroups(): array          // default: config('features.route_groups')
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

### Auto-discovering features

Use the `HasFeatures` trait in any service provider to automatically register all features from a directory. In your app, add it to `AppServiceProvider`:

```php
use Edalzell\Features\Concerns\HasFeatures;

class AppServiceProvider extends ServiceProvider
{
    use HasFeatures;

    public function register(): void
    {
        $this->registerFeatures(app_path('../features'), 'App\\Features');
    }
}
```

For a package, add it to your package's main service provider:

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

In a package, `registerFeatures()` defaults to looking in `<package-root>/features/` and registering providers under `YourPackage\Features\FeatureName\ServiceProvider`. Pass explicit arguments to override either default:

```php
$this->registerFeatures('/path/to/features', 'My\\Namespace\\Features');
```

### Features outside the app

A feature works from anywhere — the app, a package, or a directory outside the app entirely, such as a monorepo where two apps share one set of features:

```
gym/
├── apps/
│   ├── server/
│   └── mobile/
└── shared/
    └── features/
        └── Scheduling/
```

Two things need wiring, in each app that uses them.

**Autoloading** — declare the directory and its namespace in `composer.json`, and the plugin generates PSR-4 entries for every feature it finds:

```json
"extra": {
    "laravel-features": {
        "paths": {
            "../../shared/features": "Shared\\Features"
        }
    }
}
```

**Registration** — point `registerFeatures()` at the same directory:

```php
$this->registerFeatures(base_path('../../shared/features'), 'Shared\\Features');
```

The app's own `features/` directory is still scanned, so app-local and shared features can coexist.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
