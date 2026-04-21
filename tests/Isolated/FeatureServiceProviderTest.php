<?php

use Edalzell\Features\Providers\FeatureServiceProvider;
use Illuminate\Support\Facades\Event;

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

class Listener
{
    public function handle(): void {}
}
