<?php

namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The Location facade
 *
 * Class Location
 * @package Stevebauman\Location\Facades
 */
class Location extends Facade
{
    protected static function getFacadeAccessor() { return 'location'; }
}