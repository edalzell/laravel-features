<?php

namespace Edalzell\Features\Tests\Fixtures\Seeders;

use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;

#[SeedAfter(Uncollected::class)]
class Zeta extends Seeder
{
    public function run(): void {}
}
