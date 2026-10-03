<?php

namespace Stevebauman\Location;

use Closure;

class Deadline
{
    /**
     * The timestamp the current deadline expires at.
     */
    protected static ?float $expiresAt = null;

    /**
     * Run the callback with a deadline of the given seconds, if any.
     */
    public static function for(?float $seconds, Closure $callback): mixed
    {
        $previous = static::$expiresAt;

        static::$expiresAt = is_null($seconds) ? null : microtime(true) + $seconds;

        try {
            return $callback();
        } finally {
            static::$expiresAt = $previous;
        }
    }

    /**
     * Get the seconds left, or null when there is no deadline.
     */
    public static function remaining(): ?float
    {
        return is_null(static::$expiresAt)
            ? null
            : max(static::$expiresAt - microtime(true), 0.0);
    }
}
