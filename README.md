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
| Boot | Commands, Config publishing, Listeners, Policies, Routes, Seeders, then any [extensions](#extensions) — Livewire components ship as one |

## Route groups

A feature's route files are put in a route group, chosen by filename, the same way the framework does it for an application's own route files:

| File | Group |
|---|---|
| `routes/web.php` | `web` middleware |
| `routes/api.php` | `api` middleware, `api` prefix |
| anything else | no middleware group, no prefix |

Without this, `loadRoutesFrom()` is a bare `require` — a feature's `routes/web.php` would get no session or CSRF, and `routes/api.php` no throttling and no prefix.

Routes are loaded on **boot**, not register. A route file may reach for a macro that another package defines — `Route::livewire()` is the common one — and packages register in discovery order, so this one can run well before the package supplying the macro. Booting happens once every provider has registered, which is also where the framework loads its own route files.

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

The two defaults always sit underneath whatever config or a feature provides, so setting one entry never loses the others. Set an entry to `null` and that file gets no middleware group and no prefix — only what it declares itself.

An entry can also be a closure, which is handed the file's path and the `Features` instance and loads it itself — for when a route file has to be registered through something other than a plain route group:

```php
protected function routeGroups(): array
{
    return ['admin' => fn (string $path) => Admin::routes(fn () => require $path)];
}
```

Closures can't be cached, so from `config/features.php` name an invokable class instead. It's resolved from the container and called the same way, so a package can set a group for every feature at once:

```php
// config/features.php
'route_groups' => [
    'admin' => App\Features\AdminRoutes::class,
],
```

## Livewire components

Livewire only looks for components in the app's own locations, so it never sees a feature's. Put class components in `src/Livewire`, and single- or multi-file components under `resources/views` — `livewire/` for ordinary components and `pages/` for full pages, the same split Livewire uses for the app. The views root is registered as the feature's Livewire namespace, so both directories answer under one name with the directory as a dotted prefix:

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

Single- and multi-file components follow Livewire's own rules — a single-file component is a `.blade.php` holding a `new class extends Component` block, a multi-file component a directory holding `name.php` and `name.blade.php` alongside any `name.js` or `name.css`. Nothing is scanned: Livewire resolves a name the first time it is used, exactly as it does for the app's own components.

The feature's slug goes in front so two features can each have a `PostList`. Change it, or drop it, through the feature's options:

```php
protected function options(): array
{
    return ['livewire.namespace' => ''];
}
```

An empty string registers the feature's directories as plain locations instead, so its components answer to their bare names.

### Full-page components

A feature's namespace is a peer of Livewire's own `pages::` and `layouts::` — those are just default entries in the app's `component_namespaces`, not a separate mechanism — so a feature routes one of its components as a full page from its own `routes/web.php`, with no extra registration:

```php
Route::livewire('posts/create', 'my-great-feature::create-post');
```

The page renders into `livewire.component_layout`, the app's layout. To give a feature its own, point a component at a view from the feature's `resources/views`:

```php
#[Layout('my-great-feature::layout')]
```

This needs Livewire 4, which introduced both the namespace registration and view-based components. Livewire is not a dependency of this package: when it isn't installed, nothing is registered and nothing breaks.

## Extensions

Livewire isn't part of Laravel, and neither is anything else a feature might want autoloaded — Statamic fieldtypes and tags, say. Rather than teach the core about each one, a package hooks in with an extension: implement `Edalzell\Features\Contracts\Extension` and tag it in your service provider. Every feature hands itself over once its own Laravel pieces have booted:

```php
use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\Features;

class StatamicExtensions implements Extension
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

The tag is resolved through the container, so a package only has to tag itself — Laravel's package discovery does the rest. Tag it in `register()`: a feature only sees the extensions tagged before it boots. The tag resolves a fresh instance per feature unless you bind a singleton, so an extension that remembers which features it has seen should be one. Per-feature settings go through `options()` on the feature's service provider, keyed by dotted name; the core never reads them, each extension reads its own. Livewire support is the built-in extension, `Edalzell\Features\Extensions\LivewireComponents`; its `livewireNamespace()` override became the `livewire.namespace` option.

## Seeders

Every feature's `database/seeders` are collected into `Edalzell\Features\Seeders`; call it from the app's `DatabaseSeeder` to run them:

```php
public function run(): void
{
    $this->call(Seeders::class);
}
```

They run in the order their features were registered — alphabetical, for an app's own `features/` directory. That order breaks down once one feature's data depends on another's.

`#[SeedAfter]` names the seeders that must run first:

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

A seeder with no `#[SeedAfter]`, or whose dependencies are already satisfied, keeps its registration position — that's the tiebreak, not alphabetical or random order. Naming a seeder outside the collected set is fine and is simply ignored for ordering; that one is the app's own `database/seeders` to order. A cycle between `#[SeedAfter]` declarations throws a `LogicException` naming the seeders involved.

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
protected function configFileName(): string      // default: kebab-cased feature name
protected function configGroup(): string         // default: host Composer package short name when registered via package `features/` (e.g. transformstudios/prime → prime); merge key `{group}.{file}`, publish to `config/{group}/{file}.php`
protected function configPublishHandle(): string // default: kebab-cased feature name
protected function publishesConfig(): bool       // default: true, or whatever `registerFeatures()` stamped; false still merges `{group}.{file}` but offers nothing to `vendor:publish`
protected function featuresPath(): string        // default: derived from the provider's own location
protected function options(): array              // default: []; settings for extensions, e.g. ['livewire.namespace' => '']
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
            ->configPublishHandle($this->configPublishHandle())
            ->options($this->options())
            ->routeGroups(config('features.route_groups'));
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

`Features` derives the path, namespace, and app from your provider via reflection. You only need to call the fluent setters when overriding the defaults — except `routeGroups()`, which `FeatureServiceProvider` reads from config for you; without it a route file gets no group at all.

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

#### Where a package feature's config lives

A package feature's config is merged at `{group}.{file}`, where the group defaults to the package's Composer short name — `transformstudios/prime` + `features/Maps/config/maps.php` is read as `config('prime.maps.…')`. A site can then override it in one of two places, and the package picks which by whether it publishes:

| Convention | Site overrides in | `registerFeatures()` |
|---|---|---|
| A directory per package | `config/prime/maps.php` | default — each feature publishes its file there |
| Nested in the package's own config | `config/prime.php` under a `maps` key | `registerFeatures(publishesConfig: false)` |

Opting out only skips `vendor:publish`; the feature's defaults are still merged, so anything the site doesn't set keeps its default. Pick one convention per package: if a site ends up with both `config/prime.php['maps']` and `config/prime/maps.php`, Laravel loads the nested file last and it silently replaces the whole `maps` key.

### Looking up registered features

`registerFeatures()` also records each discovered feature in `FeatureRegistry`. Use that when something outside the feature needs its path or namespace — Setup runners, for example — without re-scanning disk or hardcoding conventions:

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

`registerFeatures()` records each feature via `FeatureRegistry::register()`, which also registers that feature's `ServiceProvider`. `Feature::path()` / `namespace()` with no argument return the feature root. `has()` checks that a relative path exists on disk. Use `FeatureRegistry::add()` when you only need the descriptor without loading the provider (e.g. tests).

### Finding classes in a feature

Commands, policies, listeners and seeders are found by scanning a directory for the classes its files declare. The same scan is yours for any directory under `src/`, so a convention of your own — setup writers in `src/Setup`, say — doesn't need a second one:

```php
use Edalzell\Features\FeatureRegistry;

app(FeatureRegistry::class)
    ->classes('Setup', Writer::class)
    ->each(fn (string $writer) => $writer::run());
```

`classes()` is on `FeatureRegistry` for every registered feature at once, on `Feature` for one, and on `Features` inside a provider or an [extension](#extensions). The directory is relative to `src/` and is searched recursively, sorted by path; pass a `depth` to stop sooner (`0` is the directory itself). Without a base class every declared class is returned by name, and nothing is autoloaded. With one, only concrete classes that extend or implement it are kept.

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
