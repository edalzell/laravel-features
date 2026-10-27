<?php

namespace Edalzell\Features;

use Illuminate\Support\Collection;
use PhpToken;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/** A directory, nested ones included, to the classes its PHP files declare. */
class ClassFinder
{
    public function __construct(private readonly string $directory) {}

    /**
     * The first class declared in the file; interfaces, enums and traits are
     * skipped. `Foo::class` and `new class` are `T_CLASS` tokens too, so the
     * token before decides.
     */
    public function className(string $file): ?string
    {
        // Not `isIgnorable()`: that drops the open tag too, and the class token
        // needs a token before it to be seen.
        $tokens = collect(PhpToken::tokenize(file_get_contents($file)))
            ->reject->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])
            ->values();

        if (($at = $tokens->sliding(3)->search(fn (Collection $window) => $this->declaresClass($window))) === false) {
            return null;
        }

        $class = $tokens[$at + 2]->text;
        $namespace = $tokens->take($at + 1)->sliding(2)->last(fn (Collection $window) => $this->declaresNamespace($window));

        return is_null($namespace) ? $class : "{$namespace->last()->text}\\{$class}";
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

    /** @param  Collection<int, PhpToken>  $window  the token before, `class`, the name */
    private function declaresClass(Collection $window): bool
    {
        [$previous, $token, $next] = $window->values()->all();

        return $token->is(T_CLASS) && $next->is(T_STRING) && ! $previous->is([T_DOUBLE_COLON, T_NEW]);
    }

    /** @param  Collection<int, PhpToken>  $window  `namespace`, the name */
    private function declaresNamespace(Collection $window): bool
    {
        [$token, $next] = $window->values()->all();

        return $token->is(T_NAMESPACE) && $next->is([T_STRING, T_NAME_QUALIFIED]);
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
