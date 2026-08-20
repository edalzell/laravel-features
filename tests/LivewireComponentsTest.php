<?php

use Edalzell\Features\Features;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Counter;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\Index;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\ShowPost;
use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\Livewire;

function finder(): mixed
{
    return app('livewire.finder');
}

it('resolves a class component under the feature namespace', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(finder()->resolveClassComponentClassName('sibling::counter'))->toBe(Counter::class);
});

it('resolves a nested class component', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(finder()->resolveClassComponentClassName('sibling::posts.show-post'))->toBe(ShowPost::class);
});

it('resolves an index class component by its directory name', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(finder()->resolveClassComponentClassName('sibling::posts'))->toBe(Index::class);
});

it('resolves a single-file component', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(tidy(finder()->resolveSingleFileComponentPath('sibling::greeting')))
        ->toBe(tidy(fixturePath('Sibling/resources/views/livewire/greeting.blade.php')));
});

it('resolves a multi-file component', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(tidy(finder()->resolveMultiFileComponentPath('sibling::checklist')))
        ->toBe(tidy(fixturePath('Sibling/resources/views/livewire/checklist')));
});

it('names a component with the feature namespace going back', function () {
    (new SiblingServiceProvider(app()))->boot();

    expect(finder()->normalizeName(Counter::class))->toBe('sibling::counter');
});

it('can resolve components under their bare names', function () {
    (new Features(new SiblingServiceProvider(app())))
        ->livewireNamespace('')
        ->bootLivewireComponents();

    expect(finder()->resolveClassComponentClassName('counter'))->toBe(Counter::class)
        ->and(tidy(finder()->resolveSingleFileComponentPath('greeting')))
        ->toBe(tidy(fixturePath('Sibling/resources/views/livewire/greeting.blade.php')));
});

it('registers nothing when livewire isnt installed', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('bound')->with('livewire')->andReturn(false)
        ->getMock();

    Livewire::partialMock()
        ->shouldNotReceive('addLocation')
        ->shouldNotReceive('addNamespace');

    (new Features(new SiblingServiceProvider($app)))->bootLivewireComponents();
});
