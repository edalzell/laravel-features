<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureRegistry;

it('stores and returns registered features', function () {
    $registry = new FeatureRegistry;
    $mail = new Feature(name: 'Mail', rootPath: '/features/Mail', rootNamespace: 'App\\Features\\Mail');

    $registry->add($mail);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()->first())->toBe($mail)
        ->and($registry->get('Mail'))->toBe($mail)
        ->and($registry->get('Missing'))->toBeNull();
});

it('replaces a feature when the same name is added twice', function () {
    $registry = new FeatureRegistry;
    $first = new Feature(name: 'Mail', rootPath: '/a', rootNamespace: 'A\\Mail');
    $second = new Feature(name: 'Mail', rootPath: '/b', rootNamespace: 'B\\Mail');

    $registry->add($first);
    $registry->add($second);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->get('Mail'))->toBe($second);
});
