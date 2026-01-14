<?php

namespace Edalzell\Features;

class Seeders
{
    private array $seeders = [];

    public function add(array $seeders): void
    {
        $this->seeders = array_merge($this->seeders, $seeders);
    }

    public function get(): array
    {
        return $this->seeders;
    }
}
