<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Stevebauman\Location\Position|bool get(string $ip = null)
 * @method static void setDriver(\Stevebauman\Location\Drivers\Driver $driver)
 *
 * @see \Stevebauman\Location\Location
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
        return 'location';
    }
}
