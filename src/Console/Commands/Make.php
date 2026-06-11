<?php

namespace Edalzell\Features\Console\Commands;

use Closure;
use Composer\Console\Input\InputArgument;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Pipeline;

class Make extends GeneratorCommand
{
    protected $aliases = ['make:feature'];

    protected $description = 'Create a new feature';

    protected $name = 'feature:make';

    protected $type = 'Feature';

    private ?string $package;

    /**
     * @return bool|null
     */
    public function handle()
    {
        $this->package = $this->argument('package');

        if (parent::handle() === false) {
            return false;
        }

        addComposerScript();

        if ($this->isPackageFeature()) {
            $this->ensureServiceProviderHasFeatures();
        }

        return null;
    }

    protected function getArguments()
    {
        $args = parent::getArguments();

        $args[] = ['package', InputArgument::OPTIONAL, 'Package to add '.strtolower($this->type).' to'];

        return $args;
    }

    protected function getPath($name)
    {
        $prefix = $this->isPackageFeature() ? "vendor/{$this->package}/" : '';

        return base_path("{$prefix}features/{$this->getNameInput()}/src/ServiceProvider.php");
    }

    protected function getStub()
    {
        if ($this->isPackageFeature()) {
            return __DIR__.'/../../../stubs/package-provider.stub';
        }

        return __DIR__.'/../../../stubs/provider.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->isPackageFeature()) {
            return $rootNamespace."\Features\\{$this->getNameInput()}";
        }

        return $rootNamespace."\\{$this->getNameInput()}";
    }

    protected function rootNamespace()
    {
        if ($this->isPackageFeature()) {
            $composerJson = json_decode(
                file_get_contents(base_path("vendor/{$this->package}/composer.json")),
                true
            );

            return rtrim(array_key_first($composerJson['autoload']['psr-4'] ?? []), '\\');
        }

        return 'Features\\';
    }

    private function isPackageFeature(): bool
    {
        return ! is_null($this->package);
    }

    private function ensureServiceProviderHasFeatures(): void
    {
        $path = $this->findPackageServiceProviderPath();

        if (! $path || ! file_exists($path)) {
            $this->components->warn('Could not locate package ServiceProvider to configure HasFeatures.');

            return;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'HasFeatures')) {
            return;
        }

        $content = Pipeline::send($content)
            ->through([
                $this->addHasFeaturesImport(...),
                $this->addHasFeaturesTrait(...),
                $this->addRegisterFeaturesCall(...),
            ])->thenReturn();

        file_put_contents($path, $content);

        $this->components->info("Added <comment>HasFeatures</comment> to [{$path}].");
    }

    private function addHasFeaturesImport(string $content, Closure $next): string
    {
        if (preg_match('/^use [^\n]+;$/m', $content)) {
            // append after the last existing import statement
            $content = preg_replace(
                '/((?:^use [^\n]+;\n)+)/m',
                '$1use Edalzell\\Features\\Concerns\\HasFeatures;'.PHP_EOL,
                $content,
                1
            );

            return $next($content);
        }

        // no imports exist — insert after the namespace declaration
        $content = preg_replace(
            '/^(namespace [^;]+;)/m',
            '$1'.PHP_EOL.PHP_EOL.'use Edalzell\\Features\\Concerns\\HasFeatures;',
            $content,
            1
        );

        return $next($content);
    }

    private function addHasFeaturesTrait(string $content, Closure $next): string
    {
        // insert the trait use as the first line inside the class body
        $content = preg_replace(
            '/(class [^\n]+\n\{)/s',
            '$1'.PHP_EOL.'    use HasFeatures;'.PHP_EOL,
            $content,
            1
        );

        return $next($content);
    }

    private function addRegisterFeaturesCall(string $content, Closure $next): string
    {
        if (str_contains($content, 'function register(')) {
            // insert registerFeatures() as the first line of the existing method body
            $content = preg_replace(
                '/(function register\([^)]*\)[^{]*\{)/',
                '$1'.PHP_EOL.'        $this->registerFeatures();',
                $content,
                1
            );

            return $next($content);
        }

        $method = PHP_EOL.'    public function register(): void'
            .PHP_EOL.'    {'
            .PHP_EOL.'        $this->registerFeatures();'
            .PHP_EOL.'    }';

        $content = substr_replace($content, $method.PHP_EOL, strrpos($content, '}'), 0);

        return $next($content);
    }

    private function findPackageServiceProviderPath(): ?string
    {
        $composerJsonPath = base_path("vendor/{$this->package}/composer.json");

        if (! file_exists($composerJsonPath)) {
            return null;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        $providers = $composerJson['extra']['laravel']['providers'] ?? [];
        $psr4 = $composerJson['autoload']['psr-4'] ?? [];

        if (empty($providers) || empty($psr4)) {
            return null;
        }

        $rootNamespace = rtrim(array_key_first($psr4), '\\');
        $rootPath = rtrim(reset($psr4), '/');

        $providerClass = $providers[0];
        $relativeClass = str_replace($rootNamespace.'\\', '', $providerClass);
        $relativeFile = str_replace('\\', '/', $relativeClass).'.php';

        return base_path("vendor/{$this->package}/{$rootPath}/{$relativeFile}");
    }
}
