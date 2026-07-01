<?php

use Edalzell\Features\Seeders;
use Mockery\MockInterface;

it('accumulates seeders across multiple add calls', function () {
    $seeders = new Seeders;
    $seeders->add(['SeederA', 'SeederB']);
    $seeders->add(['SeederC']);

    $prop = new ReflectionProperty($seeders, 'seeders');

    expect($prop->getValue($seeders))->toBe(['SeederA', 'SeederB', 'SeederC']);
});

it('calls each registered seeder when run', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add(['SeederA', 'SeederB']);

    $seeders->shouldReceive('call')->once()->with(['SeederA', 'SeederB']);

    $seeders->run();
});
