<?php

namespace SilentZ\Features;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

readonly class Feature
{
    public function __construct(
        public string $name,
        public ?string $provider = null,
    ) {}

    public static function fromDirectory(SplFileInfo $file): self
    {
        $feature = $file->getRelativePathname();
        $path = $file->getRealPath();

        $providers = Finder::create()
            ->in($path)
            ->files()
            ->name('*.php')
            ->filter(fn (SplFileInfo $file) => is_subclass_of(
                'App\\Features\\'.$feature.'\\'.$file->getFilenameWithoutExtension(),
                ServiceProvider::class
            ));

        if ($providers->count() == 0) {
            return new self($feature);
        }

        $provider = Arr::first(iterator_to_array($providers));

        return new self(
            $feature,
            'App\\Features\\'.$feature.'\\'.$provider->getFilenameWithoutExtension()
        );
    }
}
