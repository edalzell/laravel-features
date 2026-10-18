<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;
use Illuminate\Contracts\Foundation\Application;

it('stores and returns registered features', function () {
    $registry = new FeatureRegistry(mock(Application::class));
    $mail = new Feature('/features/Mail', 'App\\Features');

    $registry->add($mail);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()->first())->toBe($mail)
        ->and($registry->get('/features/Mail'))->toBe($mail)
        ->and($registry->get('/features/Missing'))->toBeNull();
});

it('keeps two features that share a name but not a path', function () {
    $registry = new FeatureRegistry(mock(Application::class));
    $first = new Feature('/a/Mail', 'A');
    $second = new Feature('/b/Mail', 'B');

    $registry->add($first);
    $registry->add($second);

    expect($registry->all())->toHaveCount(2)
        ->and($registry->get('/a/Mail'))->toBe($first)
        ->and($registry->get('/b/Mail'))->toBe($second);
});

it('replaces a feature when the same path is added twice', function () {
    $registry = new FeatureRegistry(mock(Application::class));
    $first = new Feature('/a/Mail', 'A');
    $second = new Feature('/a/Mail', 'B');

    $registry->add($first);
    $registry->add($second);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->get('/a/Mail'))->toBe($second);
});

it('records the feature and registers its service provider', function () {
    $mail = new Feature('/features/Mail', 'App\\Features');

    $app = mock(Application::class);
    $app->shouldReceive('register')
        ->once()
        ->with('App\\Features\\Mail\\ServiceProvider');

    $registry = new FeatureRegistry($app);
    $registry->register($mail);

    expect($registry->get('/features/Mail'))->toBe($mail);
});
