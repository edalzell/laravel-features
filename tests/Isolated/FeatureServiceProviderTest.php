<?php

use Edalzell\Features\FeatureServiceProvider;
use Illuminate\Support\Facades\Event;

it('register listeners', function () {
    Event::fake();
    mockOnDemandDisk('features/TwoWords')->put('src/Listeners/Bar.php', '');

    $this->mock('alias:Illuminate\Foundation\Events\DiscoverEvents')
        ->shouldReceive('guessClassNamesUsing')->andReturn()
        ->shouldReceive('within')
        ->andReturn(['the-event' => [Listener::class]]);

    $provider = mock(ServiceProvider::class, [mock()])->shouldAllowMockingProtectedMethods()->makePartial();

    $provider->bootListeners();

    Event::assertListening('the-event', Listener::class);
});

class Listener
{
    public function handle(): void {}
}

class ServiceProvider extends FeatureServiceProvider
{
    protected function name(): string
    {
        return 'TwoWords';
    }
}
