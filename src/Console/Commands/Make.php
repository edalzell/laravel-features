<?php

namespace Edalzell\Features\Console\Commands;

use Composer\Console\Input\InputArgument;
use Illuminate\Console\GeneratorCommand;

class Make extends GeneratorCommand
{
    protected $aliases = 'make:feature';

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

        $return = parent::handle();

        addComposerScript();

        return $return;
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
        return $rootNamespace."\Features\\{$this->getNameInput()}";
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

        return parent::rootNamespace();
    }

    private function isPackageFeature(): bool
    {
        return ! is_null($this->package);
    }
}
