<?php

namespace Edalzell\Features;

use Illuminate\Support\Collection;
use PhpToken;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/** Turns PHP files into the classes they declare. */
class ClassFinder
{
    /**
     * The first class declared in the file, fully qualified. Whitespace and comments
     * are dropped first so `Foo::class` and `new class` can be told apart from a declaration.
     */
    public static function className(string $file): ?string
    {
        $tokens = collect(PhpToken::tokenize(file_get_contents($file)))
            ->reject->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])
            ->values();

        if (is_null($declaration = self::classDeclaration($tokens))) {
            return null;
        }

        [, $class, $name] = $declaration->values()->all();
        $namespace = self::namespaceBefore($tokens, $class);

        return is_null($namespace) ? $name->text : "{$namespace}\\{$name->text}";
    }

    /**
     * The classes declared under a directory, sorted by path. A depth limits the
     * search (0 is the directory itself); a base keeps only concrete subclasses.
     *
     * @param  class-string|null  $base
     * @return Collection<int, class-string>
     */
    public static function classes(string $directory, ?string $base = null, ?int $depth = null): Collection
    {
        if (! is_dir($directory)) {
            return collect();
        }

        // @phpstan-ignore-next-line return.type — a name parsed from a file can't be proven a class-string
        return collect(self::finder($directory, $depth))
            ->keys()
            ->map(fn (string $path) => self::className($path))
            ->filter()
            ->when($base, fn (Collection $classes, string $base) => $classes->filter(fn (string $class) => self::isConcrete($class, $base)))
            ->values();
    }

    /**
     * The first [before, `class`, name] run, e.g. [`abstract`, `class`, `Foo`].
     *
     * @param  Collection<int, PhpToken>  $tokens
     * @return Collection<int, PhpToken>|null
     */
    private static function classDeclaration(Collection $tokens): ?Collection
    {
        return $tokens->sliding(3)->first(fn (Collection $candidate) => self::declaresClass($candidate));
    }

    /** @param  Collection<int, PhpToken>  $candidate  the token before, `class`, the name */
    private static function declaresClass(Collection $candidate): bool
    {
        [$previous, $token, $next] = $candidate->values()->all();

        return $token->is(T_CLASS) && $next->is(T_STRING) && ! $previous->is([T_DOUBLE_COLON, T_NEW]);
    }

    /** @param  Collection<int, PhpToken>  $candidate  `namespace`, the name */
    private static function declaresNamespace(Collection $candidate): bool
    {
        [$token, $next] = $candidate->values()->all();

        return $token->is(T_NAMESPACE) && $next->is([T_STRING, T_NAME_QUALIFIED]);
    }

    private static function finder(string $directory, ?int $depth): Finder
    {
        $finder = (new Finder)->files()->in($directory)->name('*.php')->sortByName();

        return is_null($depth) ? $finder : $finder->depth("<= {$depth}");
    }

    /** @param  class-string  $base */
    private static function isConcrete(string $class, string $base): bool
    {
        return is_subclass_of($class, $base) && ! (new ReflectionClass($class))->isAbstract();
    }

    /**
     * The last namespace declared before the class token; a braced file can declare several.
     *
     * @param  Collection<int, PhpToken>  $tokens
     */
    private static function namespaceBefore(Collection $tokens, PhpToken $class): ?string
    {
        return $tokens
            ->takeUntil($class)
            ->sliding(2)
            ->last(fn (Collection $candidate) => self::declaresNamespace($candidate))
            ?->last()
            ->text;
    }
}
