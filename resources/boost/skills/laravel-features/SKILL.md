---
name: laravel-features
description: Use when building or modifying a self-contained "feature" in an app or package that uses edalzell/laravel-features, when deciding where a feature's routes, migrations, views, policies, seeders, or config belong, or when debugging why a feature's routes, migrations, or events aren't being picked up.
---

# Laravel Features (`edalzell/laravel-features`)

A feature is a mini Laravel app living in its own folder. Instead of scattering a chunk of functionality across the app's `routes/`, `database/migrations/`, etc., it gets its own self-contained directory with the same folder names, and the package wires it into the real app automatically.

```
features/MyGreatFeature/
├── config/my-great-feature.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/
├── resources/views/
├── routes/
│   ├── web.php
│   └── api.php
└── src/
    ├── Console/Commands/
    ├── Listeners/
    ├── Policies/
    ├── Models/
    └── ServiceProvider.php
```

Everything is discovered by convention from `src/ServiceProvider.php`'s own location — nothing needs an explicit path unless you're pointing at a directory outside the normal `features/` folder.

## Booted vs registered

This is the part that fails silently — get a file in the wrong place and nothing errors, it just doesn't load.

| Phase | What | Source |
|---|---|---|
| **register()** | Config (merged), Migrations, Seeders (container binding), Translations, Views | `database/migrations/`, `lang/`, `resources/views/`, `config/` |
| **boot()** | Commands, Config (published for `vendor:publish`), Listeners, Livewire components, Policies, Routes, Seeders (discovered & queued) | `src/Console/Commands/`, `src/Listeners/`, `src/Livewire/`, `src/Policies/`, `routes/`, `database/seeders/` |

Routes boot rather than register so a route file can use a macro (`Route::livewire()`) from a package registered later.

Everything is discovered by scanning a fixed subfolder — `src/Console/Commands`, `src/Listeners`, `src/Policies`, `database/seeders`. A class in the wrong folder isn't wired up, and there's no warning: a listener outside `src/Listeners` just never fires, a policy outside `src/Policies` is never registered with `Gate`, a seeder outside `database/seeders` never gets added to the run. Same for the top-level folders: migrations only load from `database/migrations`, config only from `config/<slug>.php` (kebab-case of the feature name, unless overridden), views only from `resources/views`.

## Route groups

A feature's route files are put in a route group by filename, the same way the framework groups an application's own `routes/web.php` and `routes/api.php`:

| File | Group |
|---|---|
| `routes/web.php` | `web` middleware |
| `routes/api.php` | `api` middleware, `api` prefix |
| anything else | no middleware group, no prefix — only what the file declares |

Without the group, `loadRoutesFrom()` is a bare `require`: no session or CSRF on `web.php`, no throttling or prefix on `api.php`.

The mapping can change in two places, merged by key over those defaults:
- For every feature, by defining `routeGroups()` on an `Edalzell\Features\Extension` tagged with `Extension::TAG`. The last one tagged wins a key.
- Per feature, by overriding `routeGroups()` on that feature's service provider. This beats extensions, and only needs the keys it changes.

Set an entry to `null` and that file gets no group — only what it declares itself.

## Extensions

Anything Laravel itself doesn't autoload (Livewire components are the built-in case) is wired by an extension: a subclass of `Edalzell\Features\Extension` tagged with `Extension::TAG` in a service provider's `register()`. Each feature is handed to `boot(Features $features)` after its own conventions have booted. Per-feature settings for extensions go through the provider's `options()`, keyed by dotted name (`livewire.namespace`).

## Seeders

Every feature's `database/seeders` are collected into `Edalzell\Features\Seeders`; the app's `DatabaseSeeder` calls that one class. They run in feature registration order; `#[SeedAfter(OtherSeeder::class)]` on a seeder makes it wait for another's.

## Boot order

A feature boots before the provider that registered it. Boot code that must run after another provider goes in `bootFeature()` with `#[BootAfter(ThatProvider::class)]` on the feature's service provider; overriding `boot()` can't wait.

## Wiring it up

**Option 1 — extend `FeatureServiceProvider`** (the default, and what `feature:make` generates):

```php
class ServiceProvider extends FeatureServiceProvider
{
    // nothing needed — everything above is auto-registered
}
```

Override any of these to customize:

```php
protected function bootFeature(): void            // the feature's own boot code, after the conventions and any `#[BootAfter]` provider
protected function configFileName(): string      // default: kebab-cased feature name
protected function configGroup(): string         // default: package short name for package features, else none; merged at `{group}.{file}`
protected function configPublishHandle(): string  // default: kebab-cased feature name
protected function publishesConfig(): bool        // default: true unless `registerFeatures(publishesConfig: false)`; false still merges
protected function featuresPath(): string         // default: derived from this class's own file location
protected function options(): array               // default: []; settings for extensions, e.g. ['livewire.namespace' => '']
protected function routeGroups(): array           // default: []; merged over the package defaults and any extension's groups
```

**Option 2 — compose a `Features` object**, when the provider already extends something else:

```php
private Features $features;

public function __construct(Application $app)
{
    parent::__construct($app);

    $this->features = (new Features($this))
        ->path($this->featuresPath())
        ->name($this->name())
        ->options($this->options())
        ->routeGroups($this->routeGroups());
}

public function boot(): void { $this->features->bootFeature(); }
public function register(): void { $this->features->registerFeature(); }
```

`Features` derives the app and namespace from the provider via reflection — only override what you need with the fluent setters.

## Getting features registered at all

A feature's `ServiceProvider` isn't found by itself — something has to call `registerFeatures()`. The app's `AppServiceProvider` (or a package's main provider) needs the `HasFeatures` trait:

```php
class AppServiceProvider extends ServiceProvider
{
    use HasFeatures;

    public function register(): void
    {
        $this->registerFeatures(); // defaults to base_path('features'), namespace 'Features'
        // publishesConfig: false when a package's feature settings nest in config/{group}.php
    }
}
```

This registers `<namespacePrefix>\<FolderName>\ServiceProvider` for every subfolder holding `src/ServiceProvider.php`. **A folder without one is silently skipped.** A folder name that doesn't match the provider's namespace (renamed folder, stale `namespace`) fails at boot with "class not found".

Each feature is also stored on `FeatureRegistry` (`app(FeatureRegistry::class)->all()`); `Feature::path()`, `namespace()` and `has()` give its location without re-deriving conventions.

## Adding a feature

```bash
php artisan feature:make MyGreatFeature
```

Creates `features/MyGreatFeature/src/ServiceProvider.php` extending `FeatureServiceProvider`; files dropped into the matching folders are picked up with no per-file registration.

For a package feature, `php artisan feature:make MyGreatFeature the-dev/my-package` also adds `HasFeatures` and a `registerFeatures()` call to the package's service provider if missing.
