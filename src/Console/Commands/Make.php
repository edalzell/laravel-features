<?php

namespace Edalzell\Features\Console\Commands;

use Composer\Console\Input\InputArgument;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class Make extends GeneratorCommand
{
    protected $description = 'Create a new feature';

    protected $name = 'make:feature';

    protected $type = 'Feature';

    public function __construct(Filesystem $files, private Composer $composer)
    {
        parent::__construct($files);

        $composer->setWorkingPath(base_path());
    }

    /**
     * @return bool|null
     */
    public function handle()
    {
        $return = parent::handle();

        $this->addComposerScript();

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
        if ($this->isPackageFeature()) {
            return base_path("vendor/{$this->argument('package')}/features/{$this->getNameInput()}/src/ServiceProvider.php");
        }

        return base_path("features/{$this->getNameInput()}/src/ServiceProvider.php");
    }

    protected function getStub()
    {
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
                file_get_contents(base_path("vendor/{$this->argument('package')}/composer.json")),
                true
            );

            return rtrim(array_key_first($composerJson['autoload']['psr-4'] ?? []), '\\');
        }

        return parent::rootNamespace();
    }

    private function addComposerScript(): void
    {
        tap($this->composer)
            ->modify(fn (array $content) => $this->addPreAutoloadDumpScript($content))
            ->dumpAutoloads();
    }

    private function addPreAutoloadDumpScript(array $content): array
    {
        $hooks = (array) ($content['scripts']['pre-autoload-dump'] ?? []);
        $hooks[] = 'Edalzell\\Features\\Composer\\FeatureNamespaces::add';

        $content['scripts']['pre-autoload-dump'] = array_unique($hooks);

        return $content;
    }

    private function isPackageFeature(): bool
    {
        return ! is_null($this->argument('package'));
    }
}
