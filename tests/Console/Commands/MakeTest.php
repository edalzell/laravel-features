<?php

use Edalzell\Features\Tests\MakeServiceProvider;
use Edalzell\Features\Tests\MakeTestCase;
use Illuminate\Support\Composer;
use Mockery\MockInterface;

pest()->extends(MakeTestCase::class);

it('does something', function () {
    // $this->instance(
    //     Composer::class,
    //     mock(Composer::class, function (MockInterface $mock) {
    //         $mock->shouldReceive('setWorkingPath')->with(base_path());
    //         $mock->shouldReceive('modify');
    //         $mock->shouldReceive('dumpAutoloads');
    //     })
    // );

    // $this->bind(
    //     MakeServiceProvider::class,
    //     mock(MakeServiceProvider::class, function (MockInterface $mock) {
    //         $mock->shouldReceive('register')->once()->andReturn();
    //         $mock->shouldReceive('addComposerScript')->andReturn();
    //     })
    // );

    $this->artisan('make:feature', ['name' => 'One']);
});
