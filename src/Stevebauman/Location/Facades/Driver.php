<?php namespace Stevebauman\Location\Facades;

use Illuminate\Support\Facades\Facade;

class Driver extends Facade {
    protected static function getFacadeAccessor() { return 'driver'; }
}