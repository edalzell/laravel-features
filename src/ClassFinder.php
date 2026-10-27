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
     * The fully qualified name of the first class declared in the file;
     * interfaces, enums and traits are skipped. `Foo::class` and `new class`
     * are `T_CLASS` tokens too, so a `class` token only counts as a declaration
     * when the token before it is neither `::` nor `new`.
     */
    public static function className(string $file): ?string
    {
        // Drop whitespace and comments so `class` sits directly between the
        // token before it (`::`, `new`) and its name; a space would otherwise
        // take one of those slots.
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
     * The classes declared by the files under a directory, sorted by path so
     * the result doesn't depend on the filesystem's order. Subdirectories are
     * searched too, down to the depth if one is given: 0 is the directory
     * itself, as Finder counts it.
     * Without a base every class is returned and nothing is autoloaded. With
     * one, only concrete classes that extend or implement it are kept.
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
     * The first run of three tokens with `class` declared in the middle, e.g.
     * [`abstract`, `class`, `Foo`]; null when the file declares no class.
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
     * The namespace a class token sits in, so its name can be qualified: the
     * last one declared before it, as a braced file can declare several.
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
