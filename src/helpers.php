<?php

use Illuminate\Support\Composer;

if (! function_exists('packageRoot')) {
    function packageRoot(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $segments = explode('/', $normalized);

        return implode('/', array_slice($segments, 0, count($segments) - 2));
    }
}

if (! function_exists('addComposerScript')) {
    function addComposerScript(): void
    {
        $hook = 'Edalzell\\Features\\Composer\\FeatureNamespaces::add';

        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $hooks = (array) ($composerJson['scripts']['pre-autoload-dump'] ?? []);

        if (in_array($hook, $hooks)) {
            return;
        }

        $composer = tap(
            app('composer'),
            fn (Composer $composer) => $composer->modify(function (array $content) use ($hook) {
                $hooks = (array) ($content['scripts']['pre-autoload-dump'] ?? []);
                $hooks[] = $hook;
                $content['scripts']['pre-autoload-dump'] = $hooks;

                return $content;
            }))->dumpAutoloads();
    }
}
