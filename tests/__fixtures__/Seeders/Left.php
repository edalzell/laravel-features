<?php

namespace Edalzell\Features\Tests\Fixtures\Seeders;

use Edalzell\Features\Attributes\SeedAfter;
use Illuminate\Database\Seeder;

#[SeedAfter(Alpha::class)]
class Left extends Seeder
{
    public function run(): void {}
}
