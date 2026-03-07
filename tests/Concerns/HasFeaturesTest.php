<?php

use Edalzell\Features\Concerns\HasFeatures;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

it('registers feature', function () {
    $featuresDisk = tap(mockOnDemandDisk('features'))->put('TwoWords/src/ServiceProvider.php', '');

    File::expects('exists')->with(base_path('features'))->andReturns(true);

    $app = mock(Application::class);

    $app
        ->shouldReceive('register')
        ->once()
        ->with('Features\\TwoWords\\ServiceProvider')
        ->andReturn();

    provider($app)->register();
});

it('doesnt register feature when no provider', function () {
    $featuresDisk = tap(mockOnDemandDisk('features'))->put('TwoWords/src/Foo.php', '');

    File::expects('exists')->with(base_path('features'))->andReturns(true);

    $app = mock(Application::class);

    $app->shouldNotReceive('register');

    provider($app)->register();
});

function provider(Application $app): ServiceProvider
{
    return new class($app) extends ServiceProvider
    {
        use HasFeatures;

        public function __construct($app)
        {
            parent::__construct($app);
        }

        public function register()
        {
            $this->registerFeatures();
        }
    };
}
