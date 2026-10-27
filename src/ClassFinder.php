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
     * skipped. `Foo::class` and `new class` are `T_CLASS` tokens too, so a
     * `class` token only counts as a declaration when the token before it is
     * neither `::` nor `new`.
     */
    public function className(string $file): ?string
    {
        // Drop whitespace and comments so `class` sits directly between the
        // token before it (`::`, `new`) and its name; a space would otherwise
        // take one of those slots.
        $tokens = collect(PhpToken::tokenize(file_get_contents($file)))
            ->reject->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])
            ->values();

        if (is_null($declaration = $this->classDeclaration($tokens))) {
            return null;
        }

        [, $class, $name] = $declaration->values()->all();
        $namespace = $this->namespaceBefore($tokens, $class);

        return is_null($namespace) ? $name->text : "{$namespace}\\{$name->text}";
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

        // @phpstan-ignore-next-line return.type — a name parsed from a file can't be proven a class-string
        return collect($this->finder())
            ->keys()
            ->map(fn (string $path) => $this->className($path))
            ->filter()
            ->when($base, fn (Collection $classes, string $base) => $classes->filter(fn (string $class) => $this->isConcrete($class, $base)))
            ->values();
    }

    /**
     * The first run of three tokens with `class` declared in the middle, e.g.
     * [`abstract`, `class`, `Foo`]; null when the file declares no class.
     *
     * @param  Collection<int, PhpToken>  $tokens
     * @return Collection<int, PhpToken>|null
     */
    private function classDeclaration(Collection $tokens): ?Collection
    {
        return $tokens->sliding(3)->first(fn (Collection $window) => $this->declaresClass($window));
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

    /**
     * The namespace a class token sits in, so its name can be qualified: the
     * last one declared before it, as a braced file can declare several.
     *
     * @param  Collection<int, PhpToken>  $tokens
     */
    private function namespaceBefore(Collection $tokens, PhpToken $class): ?string
    {
        return $tokens
            ->takeUntil($class)
            ->sliding(2)
            ->last(fn (Collection $window) => $this->declaresNamespace($window))
            ?->last()
            ->text;
    }
}
