<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;

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
