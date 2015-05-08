<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The Location facade.
 *
 * Class Location
 */
class Location extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'location';
    }
}
