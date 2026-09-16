<?php

namespace Edalzell\Features\Tests\Fixtures\Seeders;

use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;

#[SeedAfter(CycleB::class)]
class CycleA extends Seeder
{
    public function run(): void {}
}
