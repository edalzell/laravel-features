<?php

namespace SilentZ\Features\Composer;

use Composer\Script\Event;

class GenerateNamespaces
{
    public static function run(Event $event): void
    {
        $generator = $event->getComposer()->getAutoloadGenerator();

        dd('hi');
    }
}
