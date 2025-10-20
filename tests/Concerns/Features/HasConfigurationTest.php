<?php

use Edalzell\Features\Feature;
use Edalzell\Features\FeatureServiceProvider;

beforeEach(function () {
    $this->disk = tap(mockOnDemandDisk('Features/TwoWords'))->put('config/two-words.php', '');
    $this->provider = mock(new class(mock()) extends FeatureServiceProvider {});
});

it('boots config', function () {
    $this->provider
        ->shouldReceive('publish')
        ->once()
        ->with(
            ['config/two-words.php' => config_path('two-words.php')],
            'two-words-config'
        );

    (new Feature('TwoWords', $this->provider))->bootConfig();
});

it('merges config', function () {
    $this->provider
        ->shouldReceive('mergeConfig')
        ->once()
        ->with($this->disk->path('config/two-words.php'), 'two-words');

    (new Feature('TwoWords', $this->provider))->registerConfig();
});
