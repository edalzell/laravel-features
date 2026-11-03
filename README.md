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
  │       ├── lang
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
| Register | Config, Migrations, Seeders, Translations, Views |
| Boot | Commands, Config publishing, Listeners, Policies, Routes, Seeders, View publishing, then any [extensions](#extensions) — Livewire components ship as one |

Config and views are offered to `vendor:publish` as `{slug}-config` and `{slug}-views`; the views land in `resources/views/vendor/{slug}`, where Laravel already looks for overrides of the `{slug}::` namespace.

## Upgrade

### 1.x to 2.0

- `Edalzell\Features\Contracts\Extension` interface is now an abstract class `Edalzell\Features\Extension` with no-op defaults. Only implement the methods you need.
- `config/features.php` isn't used anymore, move any route group changes to `routeGroups()` (see [Route groups](#route-groups)), and delete `features.php`.
- A feature's `routeGroups()` override now merges over the defaults instead of replacing them. One that returned only `['api' => ...]` used to drop `web`; it now keeps it. Return `'web' => null` to get the old behaviour.

## Route groups

A feature's route files are put in a route group by filename, as the framework does for the app's own. Without it, `loadRoutesFrom()` is a bare `require`: no session or CSRF on `web.php`, no throttling or prefix on `api.php`.

| File | Group |
|---|---|
| `routes/web.php` | `web` middleware |
| `routes/api.php` | `api` middleware, `api` prefix |
| anything else | none |

Override `routeGroups()` on the feature's service provider to change a group. Entries merge over the defaults by key, so name only what changes. `null` removes a file's group; a closure or invokable class is handed the file's path and the `Features` instance and loads the file itself:

```php
protected function routeGroups(): array
{
    return [
        'api' => ['middleware' => ['api', 'auth:sanctum'], 'prefix' => 'api/internal'],
        'admin' => fn (string $path) => Admin::routes(fn () => require $path),
    ];
}
```

To change a group for every feature, define `routeGroups()` on a tagged [extension](#extensions):

```php
class ApiVersion extends Extension
{
    public function routeGroups(): array
    {
        return ['api' => ['middleware' => 'api', 'prefix' => 'api/v1', 'as' => 'api.v1.']];
    }
}
```

Precedence, lowest to highest: defaults, extensions (last tagged wins), the feature's own `routeGroups()`.

## Livewire components

Class components go in `src/Livewire`; single- and multi-file components under `resources/views`, in `livewire/` or `pages/` as Livewire splits them for the app. The feature's slug is the Livewire namespace, and the directory becomes a dotted prefix:

```
MyGreatFeature/
├── resources/
│   └── views/
│       ├── livewire/
│       │   ├── greeting.blade.php    -> <livewire:my-great-feature::livewire.greeting />
│       │   └── checklist/            -> <livewire:my-great-feature::livewire.checklist />
│       │       ├── checklist.php
│       │       └── checklist.blade.php
│       └── pages/
│           └── show.blade.php        -> Route::livewire('show', 'my-great-feature::pages.show')
└── src/
    └── Livewire/
        ├── PostList.php              -> <livewire:my-great-feature::post-list />
        └── Posts/
            ├── Index.php             -> <livewire:my-great-feature::posts />
            └── ShowPost.php          -> <livewire:my-great-feature::posts.show-post />
```

Nothing is scanned: Livewire resolves a name the first time it is used. Change or drop the namespace through the feature's options:

```php
protected function options(): array
{
    return ['livewire.namespace' => ''];
}
```

An empty string registers plain locations instead, so components answer to their bare names.

### Full-page components

A feature routes one of its components as a full page from its own `routes/web.php`:

```php
Route::livewire('posts/create', 'my-great-feature::create-post');
```

The page renders into the app's layout; `#[Layout('my-great-feature::layout')]` uses one from the feature's `resources/views` instead.

Needs Livewire 4. Livewire is not a dependency of this package; without it, nothing is registered.

## Extensions

An extension autoloads what Laravel itself doesn't know about: Livewire components (built in, `Edalzell\Features\Extensions\LivewireComponents`), Statamic fieldtypes, and so on. Extend `Edalzell\Features\Extension`, tag it in your service provider's `register()`, and every feature hands itself over once its own Laravel pieces have booted:

```php
use Edalzell\Features\Extension;
use Edalzell\Features\Features;

class StatamicExtensions extends Extension
{
    public function boot(Features $features): void
    {
        // $features->rootPath(), rootNamespace(), slug(), app(), provider()
        // $features->has('src/Tags'), $features->pathTo('resources/blueprints')
        // $features->classes('Tags', Tags::class)
        // $features->option('statamic.something', $default)
    }
}

// in the package's service provider's register()
$this->app->tag(StatamicExtensions::class, Extension::TAG);
```

A feature only sees extensions tagged before it boots. The tag resolves a fresh instance per feature unless you bind a singleton. Per-feature settings go through `options()` on the feature's service provider, keyed by dotted name; each extension reads its own. An extension can also supply [route groups](#route-groups).

## Boot order

A feature is registered from inside its host's `register()`, so it boots before the host and before any package registered later. When the feature's own boot code must come after another provider's, put it in `bootFeature()` and name the provider to wait for:

```php
use Edalzell\Features\Attributes\BootAfter;

#[BootAfter(App\Providers\AppServiceProvider::class)]
class ServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        $this->app['router']->pushMiddlewareToGroup('web', TrackVisits::class);
    }
}
```

`bootFeature()` runs after the feature's conventions and the named provider's `boot()`. Naming a provider that isn't registered throws a `LogicException`. Overriding `boot()` still works, but it runs at the feature's own turn and can't wait.

## Seeders

Every feature's `database/seeders` are collected into `Edalzell\Features\Seeders`; call it from the app's `DatabaseSeeder` to run them:

```php
public function run(): void
{
    $this->call(Seeders::class);
}
```

They run in feature registration order. When one feature's data depends on another's, `#[SeedAfter]` names the seeders that must run first:

```php
use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;

#[SeedAfter(ListingSeeder::class)]
class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        // ...
    }
}
```

Registration order is the tiebreak. Naming a seeder outside the collected set is ignored for ordering. A cycle throws a `LogicException` naming the seeders involved.

## Dependencies

A feature can't declare Composer dependencies of its own — it shares the app's `vendor/` and lockfile, so there's nowhere for a feature's `require` to go. Add them to the app's `composer.json`, and list them in the feature's own README so it's clear which feature needs what, and nobody removes one as unused.

A feature that needs its own dependencies to travel with it — to be installed in several apps, say — should be a package instead.

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
protected function bootFeature(): void           // the feature's own boot code, after the conventions and any `#[BootAfter]` provider
protected function configFileName(): string      // default: kebab-cased feature name
protected function configGroup(): string         // default: package short name for package features, else none; merged at `{group}.{file}`
protected function configPublishHandle(): string // default: kebab-cased feature name
protected function publishesConfig(): bool       // default: true unless `registerFeatures(publishesConfig: false)`; false still merges
protected function featuresPath(): string        // default: derived from the provider's own location
protected function options(): array              // default: []; settings for extensions, e.g. ['livewire.namespace' => '']
protected function routeGroups(): array          // default: []; merged over the package defaults and any extension's groups
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
            ->configPublishHandle($this->configPublishHandle())
            ->options($this->options());
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

The `HasFeatures` trait registers every feature in a directory. In an app, add it to `AppServiceProvider`:

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

In a package, `registerFeatures()` defaults to `<package-root>/features/` and `YourPackage\Features\FeatureName\ServiceProvider`. Pass arguments to override either:

```php
$this->registerFeatures('/path/to/features', 'My\\Namespace\\Features');
```

#### Where a package feature's config lives

A package feature's config is merged at `{group}.{file}`, the group being the package's Composer short name: `the-dev/my-package` + `features/Maps/config/maps.php` reads as `config('my-package.maps.…')`. A site overrides it in one of two places:

| Convention | Site overrides in | `registerFeatures()` |
|---|---|---|
| A directory per package | `config/my-package/maps.php` | default |
| Nested in the package's own config | `config/my-package.php` under a `maps` key | `registerFeatures(publishesConfig: false)` |

`publishesConfig: false` only skips `vendor:publish`; the defaults are still merged. Pick one: if a site has both files, Laravel loads the nested one last and it replaces the whole `maps` key.

### Looking up registered features

`registerFeatures()` records each feature in `FeatureRegistry`, for when code outside the feature needs its path or namespace:

```php
use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;

app(FeatureRegistry::class)
    ->all()
    ->filter(fn (Feature $feature) => $feature->has('src/Setup'))
    ->each(function (Feature $feature) {
        $feature->path('src/Setup');      // absolute path
        $feature->namespace('Setup');     // App\Features\Mail\Setup
    });
```

`path()` and `namespace()` with no argument return the feature root; `has()` checks a relative path exists. `FeatureRegistry::add()` records a feature without registering its provider, for tests.

### Finding classes in a feature

Commands, policies, listeners and seeders are found by scanning a directory for the classes its files declare. The same scan works for any directory under `src/`, so a convention of your own needs no second one:

```php
use Edalzell\Features\FeatureRegistry;

app(FeatureRegistry::class)
    ->classes('Setup', Writer::class)
    ->each(fn (string $writer) => $writer::run());
```

`classes()` is on `FeatureRegistry` for every feature, on `Feature` for one, and on `Features` inside a provider or [extension](#extensions). The directory is relative to `src/`, searched recursively, sorted by path; `depth` stops sooner (`0` is the directory itself). A base class keeps only concrete classes that extend or implement it; without one every declared class is returned and nothing is autoloaded.

### Features outside the app

A feature can live outside the app entirely, as in a monorepo where two apps share one set:

```
gym/
├── apps/
│   ├── server/
│   └── mobile/
└── shared/
    └── features/
        └── Scheduling/
```

Each app wires two things. Autoloading, in `composer.json`, where the plugin generates PSR-4 entries for every feature it finds:

```json
"extra": {
    "laravel-features": {
        "paths": {
            "../../shared/features": "Shared\\Features"
        }
    }
}
```

And registration, pointing `registerFeatures()` at the same directory:

```php
$this->registerFeatures(base_path('../../shared/features'), 'Shared\\Features');
```

The app's own `features/` directory is still scanned.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
