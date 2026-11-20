<?php

namespace Edalzell\Features\Tests\Fixtures\Orders;

use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\Features\Tests\Fixtures\Orders\Events\OrderPlaced;
use Edalzell\Features\Tests\Fixtures\Orders\Listeners\NotifyWarehouse;
use Edalzell\Features\Tests\Fixtures\Orders\Listeners\RecordOrderEvent;
use Edalzell\Features\Tests\Fixtures\Orders\Listeners\RecordShipment;

class ServiceProvider extends FeatureServiceProvider
{
    protected function listen(): array
    {
        return [
            'orders.shipped' => RecordShipment::class,
            'orders.*' => [RecordOrderEvent::class],
            OrderPlaced::class => [NotifyWarehouse::class],
        ];
    }
}
