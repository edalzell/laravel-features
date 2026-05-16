<?php

use Illuminate\Support\Composer;

if (! function_exists('packageRoot')) {
    function packageRoot(string $path): string
    {
        $segments = explode(DIRECTORY_SEPARATOR, $path);

        return implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, count($segments) - 2));
    }
}

if (! function_exists('addComposerScript')) {
    function addComposerScript(): void
    {
        app(Composer::class)
            ->setWorkingPath(base_path())
            ->modify(function (array $content) {
                $hooks = (array) ($content['scripts']['pre-autoload-dump'] ?? []);
                $hooks[] = 'Edalzell\\Features\\Composer\\FeatureNamespaces::add';

                $content['scripts']['pre-autoload-dump'] = array_unique($hooks);

                return $content;
            })->dumpAutoloads();
    }
}
