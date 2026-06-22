This project enables developers to have self-contained "features" in their app or package.

A feature should act very much like a mini-Laravel app, in that all folder conventions should be followed:
* `src` for code
* `config` for configuration
* `database` for the factories, migrations and seeders
* `resources` for views
* `routes` for Laravel routes

Within those folders Laravel "things" are automatically booted and/or registered as they would be in a standard Laravel app:

Booted:
* Config
* Listeners
* Policies
* Seeders

Registered:
* Config
* Migrations
* Routes
* Seeders
* Views

There are 2 ways to use it:

1) extend FeatureServiceProvider in a service provider
2) configure a Features object, passing your service provider into the constructor:
```php
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
```

# Development

Preferences:
* that only do one thing
* fluent options instead of large constructors
* tests for every code path
* closures should be reduced to only one line, making methods if required, whenever possible
* methods in classes should be grouped, public, then protected, then private. Within each group, the methods should be sorted alpabetically
* class properties should be grouped public, then protected, then private and within each group, sorted alpabetically.

# Testing
* test directory structure mirrors the code directory structure
* isolated tests must be run first (see the composer.json scripts for an example)
