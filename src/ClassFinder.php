<?php

namespace Edalzell\Features;

use Illuminate\Support\Collection;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/** A directory, nested ones included, to the classes its PHP files declare. */
class ClassFinder
{
    public function __construct(private readonly string $directory) {}

    /**
     * The first class declared in the file, or null when it declares none — an
     * interface, enum or trait, say. `Foo::class` and `new class` are `T_CLASS`
     * tokens too, so the token before decides.
     */
    public function className(string $file): ?string
    {
        $tokens = array_values(array_filter(
            token_get_all(file_get_contents($file)),
            fn (array|string $token) => is_string($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]),
        ));
        $namespace = '';

        foreach ($tokens as $i => $token) {
            if (! is_array($token)) {
                continue;
            }

            $previous = $tokens[$i - 1] ?? null;
            $next = $tokens[$i + 1] ?? null;

            if ($token[0] === T_NAMESPACE && is_array($next) && in_array($next[0], [T_STRING, T_NAME_QUALIFIED])) {
                $namespace = $next[1];
            }

            if ($token[0] !== T_CLASS || ! is_array($next) || $next[0] !== T_STRING) {
                continue;
            }

            if (is_array($previous) && in_array($previous[0], [T_DOUBLE_COLON, T_NEW])) {
                continue;
            }

            return $namespace === '' ? $next[1] : "{$namespace}\\{$next[1]}";
        }

        return null;
    }

    /**
     * Sorted by path, so the result doesn't depend on the filesystem's order.
     * Without a base nothing is autoloaded: the names come from the files alone.
     * With one, only concrete subclasses (or implementations) are kept.
     *
     * @param  class-string|null  $base
     * @return Collection<int, class-string>
     */
    public function classes(?string $base = null): Collection
    {
        if (! is_dir($this->directory)) {
            return collect();
        }

        $classes = collect($this->finder())
            ->keys()
            ->map(fn (string $path) => $this->className($path))
            ->filter();

        if (! is_null($base)) {
            $classes = $classes->filter(fn (string $class) => $this->isConcrete($class, $base));
        }

        // @phpstan-ignore-next-line return.type — a name parsed from a file can't be proven a class-string
        return $classes->values();
    }

    private function finder(): Finder
    {
        return tap(new Finder)->files()->in($this->directory)->name('*.php')->sortByName();
    }

    /** @param  class-string  $base */
    private function isConcrete(string $class, string $base): bool
    {
        return is_subclass_of($class, $base) && ! (new ReflectionClass($class))->isAbstract();
    }
}
