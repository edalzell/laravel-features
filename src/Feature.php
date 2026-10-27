<?php

namespace Edalzell\Features;

use Illuminate\Support\Collection;

readonly class Feature
{
    public string $name;

    public string $rootNamespace;

    public function __construct(
        public string $rootPath,
        string $namespacePrefix,
        public string $configGroup = '',
        public bool $publishesConfig = true,
    ) {
        $this->name = basename($this->rootPath);
        $this->rootNamespace = rtrim($namespacePrefix, '\\').'\\'.$this->name;
    }

    /**
     * The classes under a directory of `src/`, nested ones included; with a base,
     * only its concrete subclasses.
     *
     * @param  class-string|null  $base
     * @return Collection<int, class-string>
     */
    public function classes(string $directory, ?string $base = null): Collection
    {
        return ClassFinder::classes($this->path("src/{$directory}"), $base);
    }

    public function has(string $relative = ''): bool
    {
        return file_exists($this->path($relative));
    }

    public function namespace(string $relative = ''): string
    {
        if ($relative === '') {
            return $this->rootNamespace;
        }

        return $this->rootNamespace.'\\'.trim(str_replace('/', '\\', $relative), '\\');
    }

    public function path(string $relative = ''): string
    {
        if ($relative === '') {
            return $this->rootPath;
        }

        return $this->rootPath.'/'.trim(str_replace('\\', '/', $relative), '/');
    }
}
