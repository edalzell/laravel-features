<?php

namespace Edalzell\Features;

use Illuminate\Database\Seeder;

class Seeders extends Seeder
{
    private array $seeders = [];

    public function add(array $seeders): void
    {
        array_push($this->seeders, ...$seeders);
    }

    public function run(): void
    {
        $this->call($this->seeders);
    }
}
