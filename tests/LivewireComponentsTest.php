<?php

use Edalzell\Features\Features;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Counter;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\Index;
use Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts\ShowPost;
use Edalzell\Features\Tests\Fixtures\Sibling\ServiceProvider as SiblingServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\Finder\Finder;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;

/**
 * Livewire 4 resolves registered namespaces through its Finder; Livewire 3 has no
 * such thing, so each version only runs the tests for the path it takes.
 */
$livewire4 = class_exists(Finder::class);

function registeredComponent(string $name): string
{
    return app(ComponentRegistry::class)->getClass($name);
}

function finder(): mixed
{
    return app('livewire.finder');
}

it('registers nothing when livewire isnt installed', function () {
    $app = mock(Application::class)
        ->makePartial()
        ->shouldReceive('bound')->with('livewire')->andReturn(false)
        ->getMock();

    Livewire::partialMock()
        ->shouldNotReceive('component')
        ->shouldNotReceive('addNamespace');

    (new Features(new SiblingServiceProvider($app)))->bootLivewireComponents();
});

describe('livewire 3', function () {
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
})->skip($livewire4, 'Livewire 4 resolves the feature namespace instead');

describe('livewire 4', function () {
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
})->skip(! $livewire4, 'Needs Livewire 4');
