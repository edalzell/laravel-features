<?php

use Edalzell\Features\Features;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Counter;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\Index;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\ShowPost;
use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;

function registeredComponent(string $name): string
{
    return app(ComponentRegistry::class)->getClass($name);
}

it('registers a feature component under the feature namespace', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(registeredComponent('sibling.counter'))->toBe(Counter::class);
});

it('joins nested directories with dots', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(registeredComponent('sibling.posts.show-post'))->toBe(ShowPost::class);
});

it('drops index so a subdirectory answers to its own name', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(registeredComponent('sibling.posts'))->toBe(Index::class);
});

it('names a component the same way going back', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(Livewire::new('sibling.counter')->getName())->toBe('sibling.counter');
});

it('wont register a class that isnt a component', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(fn () => registeredComponent('sibling.post-formatter'))
        ->toThrow(ComponentNotFoundException::class);
});

it('can register components under their bare names', function () {
    (new Features(new SiblingServiceProvider(app())))
        ->livewireNamespace('')
        ->bootLivewireComponents();

    expect(registeredComponent('counter'))->toBe(Counter::class);
});

it('wont register components when there arent any', function () {
    mockOnDemandDisk('features/TwoWords');
    [$features] = mockFeatures();

    Livewire::partialMock()->shouldNotReceive('component');

    $features->bootLivewireComponents();
});

it('wont register components when livewire isnt installed', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('bound')->with('livewire')->andReturn(false)
        ->getMock();

    Livewire::partialMock()->shouldNotReceive('component');

    (new Features(new SiblingServiceProvider($app)))->bootLivewireComponents();
});
