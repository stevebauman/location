<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;
use Stevebauman\Location\LocationManager;

/**
 * @method static \Stevebauman\Location\Drivers\Driver[] drivers()
 * @method static \Stevebauman\Location\Position|bool    get(string $ip = null)
 * @method static void                                   resolveRequestUsing(callable $callback)
 * @method static void                                   setDriver(\Stevebauman\Location\Drivers\Driver $driver)
 */
class Location extends Facade
{
    /**
     * The IoC key accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LocationManager::class;
    }
}
