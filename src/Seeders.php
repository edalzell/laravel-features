<?php

namespace Edalzell\Features;

class Seeders
{
    private array $seeders = [];

    public function add(array $seeders): void
    {
        array_push($this->seeders, ...$seeders);
    }

    public function get(): array
    {
        return $this->seeders;
    }
}
