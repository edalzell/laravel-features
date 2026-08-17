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
├── resources/views/
├── routes/
│   ├── web.php
│   └── api.php
└── src/
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
| **register()** | Config (merged), Migrations, Routes, Seeders (container binding), Views | `src/`, `database/`, `routes/`, `resources/views/`, `config/` |
| **boot()** | Config (published for `vendor:publish`), Listeners, Policies, Seeders (discovered & queued) | `src/Listeners/`, `src/Policies/`, `database/seeders/` |

Config and Seeders each do something different at each phase: `register()` merges the config file into `config()` and creates the seeder-runner binding; `boot()` makes the config file publishable and discovers the actual seeder classes to add to the runner. Listeners and Policies are boot-only — they need the container fully wired first, so no equivalent happens during register.

Everything is discovered by scanning a fixed subfolder — `src/Listeners`, `src/Policies`, `database/seeders`. A class in the wrong folder isn't wired up, and there's no warning: a listener outside `src/Listeners` just never fires, a policy outside `src/Policies` is never registered with `Gate`, a seeder outside `database/seeders` never gets added to the run. Same for the top-level folders: migrations only load from `database/migrations`, config only from `config/<slug>.php` (kebab-case of the feature name, unless overridden), views only from `resources/views`.

## Route groups

A feature's route files are put in a route group by filename, the same way the framework groups an application's own `routes/web.php` and `routes/api.php`:

| File | Group |
|---|---|
| `routes/web.php` | `web` middleware |
| `routes/api.php` | `api` middleware, `api` prefix |
| anything else | no middleware group, no prefix — only what the file declares |

This matters because `loadRoutesFrom()` is a bare `require` — without the grouping, a feature's `web.php` routes would have no session or CSRF, and `api.php` no throttling or prefix.

The mapping is configurable in two places:
- Globally, via `config('features.route_groups')` — publish it with `php artisan vendor:publish --tag=features-config`.
- Per feature, by overriding `routeGroups()` on that feature's service provider.

Set an entry to `null` (or remove it) and that file gets no group — only what it declares itself.

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
protected function configFileName(): string      // default: kebab-cased feature name
protected function configGroup(): string         // default: '' (no subdirectory)
protected function configPublishHandle(): string  // default: kebab-cased feature name
protected function featuresPath(): string         // default: derived from this class's own file location
protected function routeGroups(): array           // default: config('features.route_groups')
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
    }
}
```

This scans the target directory for subfolders containing `src/ServiceProvider.php` and registers each as `<namespacePrefix>\<FolderName>\ServiceProvider`. **If a folder is missing `src/ServiceProvider.php`, it's silently skipped** — no error, the feature just doesn't exist as far as the app is concerned. If the folder name and the provider's actual namespace don't match (e.g. after renaming a feature folder without updating its `namespace` declaration), registration throws a "class not found" error at boot — that's the signal to check the namespace against the folder name.

## Adding a feature

```bash
php artisan feature:make MyGreatFeature
```

Creates `features/MyGreatFeature/src/ServiceProvider.php` extending `FeatureServiceProvider`. From there, drop files into the matching folders — `src/Listeners/SomeListener.php`, `routes/web.php`, `database/migrations/...`, `config/my-great-feature.php` — and they're picked up automatically; no manual registration per file.

For a package feature: `php artisan feature:make MyGreatFeature the-dev/my-package`. This also adds the `HasFeatures` trait and a `registerFeatures()` call to the target package's own service provider if it doesn't have one already.
