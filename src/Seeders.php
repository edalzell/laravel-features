<?php

namespace Edalzell\Features;

class Seeders
{
    private static array $seeders = [];

    public static function add(array $seeders): void
    {
        static::$seeders = array_merge(static::$seeders, $seeders);
    }

    public static function get(): array
    {
        return static::$seeders;
    }
}
