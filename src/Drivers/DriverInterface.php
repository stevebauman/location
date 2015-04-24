<?php

namespace Stevebauman\Location\Drivers;

interface DriverInterface {

    public function get($ip);
    
}