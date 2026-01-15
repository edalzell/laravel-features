[![Latest Version on Packagist](https://img.shields.io/packagist/v/edalzell/laravel-features.svg?style=flat-square)](https://packagist.org/packages/edalzell/laravel-features)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/edalzell/laravel-features/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/edalzell/laravel-features/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/edalzell/laravel-features/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/edalzell/laravel-features/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/edalzell/laravel-features.svg?style=flat-square)](https://packagist.org/packages/edalzell/laravel-features)

Add self-contained features to your Laravel app, including all resources/routes/etc.

```bash
.
└── app/
...
└── features/
  │   └── MyGreatFeature/
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

## Installation

You can install the package via composer:

```bash
composer require edalzell/laravel-features
```


## Usage

To add a feature to your app:

```bash
php artisan make:feature MyGreatFeature
```

This will create an empty (but necessary) service provider that autoloads/registers migrations, routes, & views and properly namespaces your factories, seeders and code.

If you want to add a feature manually, or convert something you already have into a feature:
* create an `features/YourFeature` folder
* create a `ServiceProvider` that extends `FeatureServiceProvider`.
* add a `pre-autoload-dump` script to your `composer.json`:
```
"pre-autoload-dump": [
    "Edalzell\\Features\\Composer\\FeatureNamespaces::add"
]
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
