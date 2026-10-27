<?php

use Edalzell\Features\Feature;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Configs\MailWriter;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\EnvWriter;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Writer;

it('joins a relative path onto the feature root', function () {
    $feature = new Feature('/pkg/features/Mail', 'App\\Features');

    expect($feature->path('src/Setup'))->toBe('/pkg/features/Mail/src/Setup')
        ->and($feature->path())->toBe('/pkg/features/Mail');
});

it('appends a relative segment onto the feature namespace', function () {
    $feature = new Feature('/pkg/features/Mail', 'App\\Features');

    expect($feature->namespace('Setup'))->toBe('App\\Features\\Mail\\Setup')
        ->and($feature->namespace())->toBe('App\\Features\\Mail')
        ->and($feature->name)->toBe('Mail');
});

it('reports whether a relative path exists', function () {
    $root = sys_get_temp_dir().'/feature-'.uniqid();
    mkdir($root.'/src/Setup', 0777, true);

    $feature = new Feature($root, 'App\\Features');

    expect($feature->has('src/Setup'))->toBeTrue()
        ->and($feature->has('src/Missing'))->toBeFalse();

    array_map('rmdir', [$root.'/src/Setup', $root.'/src', $root]);
});

it('finds classes under a directory relative to src', function () {
    $feature = new Feature(fixturePath('Discovery'), 'Edalzell\\Features\\Tests\\Fixtures');

    expect($feature->classes('Setup', Writer::class)->all())->toBe([MailWriter::class, EnvWriter::class])
        ->and($feature->classes('Setup', Writer::class, depth: 0)->all())->toBe([EnvWriter::class])
        ->and($feature->classes('Missing'))->toBeEmpty();
});
