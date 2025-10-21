<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

beforeEach(function () {
    $this->disk = mockOnDemandDisk('Features/TwoWords');
    $this->provider = mock(new class(mock()) extends FeatureServiceProvider {});
    $this->feature = new Feature('TwoWords', $this->provider);
});

it('gets path', function () {
    expect($this->feature->path('foo'))->toBe($this->disk->path('foo'));
});

it('checks for file existence', function () {
    $this->disk->put('foo/test.txt', 'contents');

    expect($this->feature->exists('foo/test.txt'))->toBeTrue();
});

it('sets slug', function () {
    expect($this->feature)->toHaveProperty('slug', 'two-words');
});

it('boots config', function () {
    $this->disk->put('config/two-words.php', '');

    $feature = mock(Feature::class, ['TwoWords', $this->provider])->makePartial();

    $feature->shouldReceive('bootConfig')->once()->andReturnSelf();

    $feature->boot();
});

it('registers all options', function () {
    $this->disk->put('config/two-words.php', '');
    $this->disk->put('database/migrations/add_table.php', '');
    $this->disk->put('resources/views/test.blade.php', '');
    $this->disk->put('routes/web.php', '');

    $feature = mock(Feature::class, ['TwoWords', $this->provider])->makePartial();

    $feature
        ->shouldReceive('registerConfig')->once()->andReturnSelf()
        ->shouldReceive('registerDatabase')->once()->andReturnSelf()
        ->shouldReceive('registerRoutes')->once()->andReturnSelf()
        ->shouldReceive('registerViews')->once()->andReturnSelf();

    $feature->register();
});
