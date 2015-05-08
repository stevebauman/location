<?php

namespace Stevebauman\Location\Drivers;

interface DriverInterface
{
    /**
     * Retrieves the location by the specified IP
     * using the current driver.
     *
     * @param string $ip
     *
     * @return \Stevebauman\Location\Objects\Location
     */
    public function get($ip);
}
