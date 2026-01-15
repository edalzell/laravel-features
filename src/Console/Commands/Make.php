<?php

namespace Edalzell\Features\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class Make extends GeneratorCommand
{
    protected $description = 'Add a new feature to the application';

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

    protected function getPath($name)
    {
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
}
