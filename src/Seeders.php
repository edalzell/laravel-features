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
     * The `#[SeedAfter]` seeders that are also collected here; others can't be ordered.
     *
     * @return array<int, string>
     */
    private function dependenciesFor(string $seeder): array
    {
        if (! class_exists($seeder)) {
            return [];
        }

        $attribute = (new ReflectionClass($seeder))->getAttributes(SeedAfter::class)[0] ?? null;

        return $attribute === null
            ? []
            : array_values(array_intersect($attribute->newInstance()->seeders, $this->seeders));
    }

    /**
     * Depth-first topological sort; registration order is the tiebreak.
     *
     * @return array<int, string>
     */
    private function sorted(): array
    {
        $sorted = [];
        $placing = [];

        $place = function (string $seeder) use (&$place, &$sorted, &$placing): void {
            if (in_array($seeder, $sorted, true)) {
                return;
            }

            throw_if(
                isset($placing[$seeder]),
                LogicException::class,
                'Circular seeder dependency detected: '.implode(' -> ', [...array_slice(array_keys($placing), array_search($seeder, array_keys($placing), true)), $seeder]),
            );

            $placing[$seeder] = true;

            foreach ($this->dependenciesFor($seeder) as $dependency) {
                $place($dependency);
            }

            unset($placing[$seeder]);

            $sorted[] = $seeder;
        };

        foreach ($this->seeders as $seeder) {
            $place($seeder);
        }

        return $sorted;
    }
}
