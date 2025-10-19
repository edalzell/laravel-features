<?php

use Edalzell\Features\Feature;

it('can check for file existence', function () {
    mockOnDemandDisk('Features/One')->put('foo/test.txt', 'contents');

    $feature = new Feature('One');

    expect($feature->exists('foo/test.txt'))->toBeTrue();
});

it('sets slug', function () {
    expect(new Feature('TwoWords'))->toHaveProperty('slug', 'two-words');
});
