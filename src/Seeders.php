<?php

namespace Edalzell\Features;

use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;
use LogicException;
use ReflectionClass;

class Seeders extends Seeder
{
    /** @var array<int, string> */
    private array $seeders = [];

    /** @param array<int, string> $seeders */
    public function add(array $seeders): void
    {
        array_push($this->seeders, ...$seeders);
    }

    public function run(): void
    {
        $this->call($this->sorted());
    }

    /**
     * A seeder's own #[SeedAfter] dependencies, narrowed to the ones actually
     * collected — a dependency living outside this list (an app's own
     * `database/seeders`, say) has nothing here to order it against.
     *
     * @param  array<int, string>  $collected
     * @return array<int, string>
     */
    private function dependenciesFor(string $seeder, array $collected): array
    {
        if (! class_exists($seeder)) {
            return [];
        }

        $attribute = (new ReflectionClass($seeder))->getAttributes(SeedAfter::class)[0] ?? null;

        if ($attribute === null) {
            return [];
        }

        return array_values(array_intersect($attribute->newInstance()->seeders, $collected));
    }

    /**
     * Kahn's algorithm, breaking ties by registration order so a seeder with no
     * `#[SeedAfter]` keeps its collected position.
     *
     * @return array<int, string>
     */
    private function sorted(): array
    {
        $seeders = $this->seeders;
        $positions = array_flip($seeders);

        $dependencies = [];
        $dependents = [];
        $remaining = [];

        foreach ($seeders as $seeder) {
            $dependencies[$seeder] = $this->dependenciesFor($seeder, $seeders);
            $remaining[$seeder] = count($dependencies[$seeder]);

            foreach ($dependencies[$seeder] as $dependency) {
                $dependents[$dependency][] = $seeder;
            }
        }

        $available = array_values(array_filter($seeders, fn (string $seeder) => $remaining[$seeder] === 0));
        $sorted = [];

        while ($available !== []) {
            usort($available, fn (string $a, string $b) => $positions[$a] <=> $positions[$b]);
            $next = array_shift($available);
            $sorted[] = $next;

            foreach ($dependents[$next] ?? [] as $dependent) {
                if (--$remaining[$dependent] === 0) {
                    $available[] = $dependent;
                }
            }
        }

        if (count($sorted) !== count($seeders)) {
            $cycle = array_diff($seeders, $sorted);

            throw new LogicException('Circular seeder dependency detected: '.implode(', ', $cycle));
        }

        return $sorted;
    }
}
