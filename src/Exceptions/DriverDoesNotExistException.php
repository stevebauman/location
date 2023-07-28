<?php

namespace Stevebauman\Location\Exceptions;

class DriverDoesNotExistException extends LocationException
{
    /**
     * Create a new exception for the non-existent driver.
     */
    public static function forDriver(string $driver): static
    {
        return new static(
            "The location driver [$driver] does not exist. Did you publish the configuration file?"
        );
    }
}
