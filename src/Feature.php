<?php

namespace Edalzell\Features;

readonly class Feature
{
    public string $name;

    public string $rootNamespace;

    public function __construct(
        public string $rootPath,
        string $namespacePrefix,
        public string $configGroup = '',
    ) {
        $this->name = basename($this->rootPath);
        $this->rootNamespace = rtrim($namespacePrefix, '\\').'\\'.$this->name;
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
