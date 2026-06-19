<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

it('registers listeners', function () {
    Event::fake();
    mockOnDemandDisk('features/TwoWords')->put('src/Listeners/Bar.php', '');
    [$features] = mockFeatures();

    $this->mock('alias:Illuminate\Foundation\Events\DiscoverEvents')
        ->shouldReceive('guessClassNamesUsing')->andReturn()
        ->shouldReceive('within')
        ->andReturn(['the-event' => [Listener::class]]);

    $features->bootListeners();

    Event::assertListening('the-event', Listener::class);
});

it('registers policies', function () {
    mockOnDemandDisk('features/TwoWords')->put('src/Policies/PostPolicy.php', '');
    [$features] = mockFeatures();

    Gate::shouldReceive('policy')
        ->once()
        ->with('Features\TwoWords\Models\Post', 'Features\TwoWords\Policies\PostPolicy');

    $features->bootPolicies();
});

class Listener
{
    public function handle(): void {}
}
