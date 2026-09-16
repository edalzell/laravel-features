<?php

namespace Edalzell\Features\Tests\Fixtures\Seeders;

use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;

#[SeedAfter(CycleA::class)]
class CycleB extends Seeder
{
    public function run(): void {}
}
