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
        parent::handle();

        $this->addComposerScript();
    }

    protected function getPath($name)
    {
        return app_path("Features/{$this->getNameInput()}/src/ServiceProvider.php");
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
        // @todo check for array and merge in
        // @todo skip if it already exists
        $content['scripts']['pre-autoload-dump'] = 'Edalzell\\Features\\Composer\\FeatureNamespaces::add';

        return $content;
    }
}
