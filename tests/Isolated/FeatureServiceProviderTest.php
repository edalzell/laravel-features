<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

it('register listeners', function () {
    Event::fake();
    mockOnDemandDisk('features/TwoWords')->put('src/Listeners/Bar.php', '');
    $provider = mockServiceProvider();

    $this->mock('alias:Illuminate\Foundation\Events\DiscoverEvents')
        ->shouldReceive('guessClassNamesUsing')->andReturn()
        ->shouldReceive('within')
        ->andReturn(['the-event' => [Listener::class]]);

    $provider->bootListeners();

    Event::assertListening('the-event', Listener::class);
});

it('registers policies', function () {
    mockOnDemandDisk('features/TwoWords')->put('src/Policies/PostPolicy.php', '');
    $provider = mockServiceProvider();
    $provider->shouldReceive('namespace')->andReturn('Features\TwoWords');

    Gate::shouldReceive('policy')
        ->once()
        ->with('Features\TwoWords\Models\Post', 'Features\TwoWords\Policies\PostPolicy');

    $provider->bootPolicies();
});

class Listener
{
    public function handle(): void {}
}
