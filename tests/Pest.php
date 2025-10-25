<?php

use Edalzell\Features\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class)->in(__DIR__);

function mockOnDemandDisk(string $path): Filesystem
{
    $localDisk = Storage::fake('local');

    Storage::shouldReceive('build')->with([
        'driver' => 'local',
        'root' => app_path($path),
    ])->andReturn($localDisk);

    return $localDisk;
}
