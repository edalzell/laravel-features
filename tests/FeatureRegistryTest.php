<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;
use Illuminate\Contracts\Foundation\Application;

it('stores and returns registered features', function () {
    $registry = new FeatureRegistry(mock(Application::class));
    $mail = new Feature(name: 'Mail', rootPath: '/features/Mail', rootNamespace: 'App\\Features\\Mail');

    $registry->add($mail);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()->first())->toBe($mail)
        ->and($registry->get('Mail'))->toBe($mail)
        ->and($registry->get('Missing'))->toBeNull();
});

it('replaces a feature when the same name is added twice', function () {
    $registry = new FeatureRegistry(mock(Application::class));
    $first = new Feature(name: 'Mail', rootPath: '/a', rootNamespace: 'A\\Mail');
    $second = new Feature(name: 'Mail', rootPath: '/b', rootNamespace: 'B\\Mail');

    $registry->add($first);
    $registry->add($second);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->get('Mail'))->toBe($second);
});

it('records the feature and registers its service provider', function () {
    $mail = new Feature(name: 'Mail', rootPath: '/features/Mail', rootNamespace: 'App\\Features\\Mail');

    $app = mock(Application::class);
    $app->shouldReceive('register')
        ->once()
        ->with('App\\Features\\Mail\\ServiceProvider');

    $registry = new FeatureRegistry($app);
    $registry->register($mail);

    expect($registry->get('Mail'))->toBe($mail);
});
