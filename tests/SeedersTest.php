<?php

use Edalzell\Features\Seeders;
use Edalzell\Features\Tests\Fixtures\Seeders\Alpha;
use Edalzell\Features\Tests\Fixtures\Seeders\Beta;
use Edalzell\Features\Tests\Fixtures\Seeders\CycleA;
use Edalzell\Features\Tests\Fixtures\Seeders\CycleB;
use Edalzell\Features\Tests\Fixtures\Seeders\Delta;
use Edalzell\Features\Tests\Fixtures\Seeders\Epsilon;
use Edalzell\Features\Tests\Fixtures\Seeders\Gamma;
use Edalzell\Features\Tests\Fixtures\Seeders\Left;
use Edalzell\Features\Tests\Fixtures\Seeders\Right;
use Edalzell\Features\Tests\Fixtures\Seeders\Zeta;
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

it('runs a seeder after the one it declares #[SeedAfter], even when added first', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add([Beta::class, Alpha::class]);

    $seeders->shouldReceive('call')->once()->with([Alpha::class, Beta::class]);

    $seeders->run();
});

it('keeps registration order when no seeders declare #[SeedAfter]', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add([Epsilon::class, Delta::class]);

    $seeders->shouldReceive('call')->once()->with([Epsilon::class, Delta::class]);

    $seeders->run();
});

it('orders a chain of three seeders correctly', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add([Gamma::class, Beta::class, Alpha::class]);

    $seeders->shouldReceive('call')->once()->with([Alpha::class, Beta::class, Gamma::class]);

    $seeders->run();
});

it('keeps registration order for two independent seeders sharing a dependency', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add([Alpha::class, Right::class, Left::class]);

    $seeders->shouldReceive('call')->once()->with([Alpha::class, Right::class, Left::class]);

    $seeders->run();
});

it('ignores a dependency that was not itself collected', function () {
    /** @var Seeders&MockInterface $seeders */
    $seeders = mock(Seeders::class)->makePartial();
    $seeders->add([Zeta::class]);

    $seeders->shouldReceive('call')->once()->with([Zeta::class]);

    $seeders->run();
});

it('throws a LogicException naming both seeders on a cycle', function () {
    $seeders = new Seeders;
    $seeders->add([CycleA::class, CycleB::class]);

    try {
        $seeders->run();
        test()->fail('Expected a LogicException.');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toContain(CycleA::class);
        expect($exception->getMessage())->toContain(CycleB::class);
    }
});
