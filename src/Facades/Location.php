<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;
use Stevebauman\Location\LocationManager;

/**
 * @method static \Stevebauman\Location\Drivers\Driver[] drivers()
 * @method static \Stevebauman\Location\Position|bool get(string $ip = null)
 * @method static void resolveRequestUsing(callable $callback)
 * @method static void setDriver(\Stevebauman\Location\Drivers\Driver $driver)
 */
class Location extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return LocationManager::class;
    }
}
