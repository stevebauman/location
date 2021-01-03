<?php

namespace Stevebauman\Location\Exceptions;

class DriverDoesNotExistException extends LocationException
{
    /**
     * Create a new exception for the non-existent driver.
     *
     * @param string $driver
     *
     * @return static
     */
    public static function forDriver($driver)
    {
        return new static(
            "The location driver [$driver] does not exist. Did you publish the configuration file?"
        );
    }
}
