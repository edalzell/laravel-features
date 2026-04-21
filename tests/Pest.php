<?php

use Edalzell\Features\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Filesystem\Path;

uses(TestCase::class)->in(__DIR__);

function mockOnDemandDisk(string $path = '', bool $package = false): Filesystem
{
    $localDisk = Storage::fake('local');
    $root = $package ? Path::normalize(__DIR__.'__fixtures__/TwoWords') : base_path($path);

    Storage::shouldReceive('build')->with([
        'driver' => 'local',
        'root' => $root,
    ])->andReturn($localDisk);

    return $localDisk;
}

function tidy(string $path): string
{
    return str_replace('/', '\\', $path);
}
