<?php

namespace SilentZ\Features;

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

        $provider = collect(Finder::create()
            ->in($file->getRealPath())
            ->files()
            ->name('*.php'))
            ->map(fn (SplFileInfo $file) => 'App\\Features\\'.$feature.'\\'.$file->getFilenameWithoutExtension())
            ->filter(fn (string $class) => is_subclass_of($class, ServiceProvider::class))
            ->first();

        return new self($feature, $provider);
    }
}
